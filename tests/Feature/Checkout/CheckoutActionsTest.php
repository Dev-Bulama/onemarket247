<?php

use App\Actions\Cart\AddCartItemAction;
use App\Actions\Checkout\CompleteCheckoutAction;
use App\Actions\Checkout\InitiateCheckoutAction;
use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\ReserveStockAction;
use App\Enums\OrderStatus;
use App\Enums\ShippingRateType;
use App\Enums\StockStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\CheckoutValidationException;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Notifications\OrderConfirmationNotification;
use Illuminate\Support\Facades\Notification;

/**
 * A free flat rate for the given country keeps this file's existing total
 * assertions (subtotal-only, no shipping) unchanged while still exercising
 * a real zone/rate lookup rather than the "rest of world" fallback.
 */
function seedFreeShippingFor(Country $country): void
{
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'rate_type' => ShippingRateType::Free, 'base_amount' => 0]);
}

function shippingDataFor(Country $country): array
{
    return [
        'customer_id' => null,
        'guest_name' => 'Jane Guest',
        'guest_email' => 'jane@example.com',
        'guest_phone' => null,
        'shipping_full_name' => 'Jane Guest',
        'shipping_phone' => null,
        'shipping_address_line_1' => '123 Main St',
        'shipping_address_line_2' => null,
        'shipping_country_id' => $country->id,
        'shipping_state_id' => null,
        'shipping_city_id' => null,
        'shipping_postal_code' => null,
    ];
}

test('initiating checkout twice for the same cart reuses the same session', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['price' => 1000]);
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);

    $session1 = app(InitiateCheckoutAction::class)->handle($cart);
    $session2 = app(InitiateCheckoutAction::class)->handle($cart);

    expect($session2->id)->toBe($session1->id)
        ->and($session1->idempotency_key)->toBe($session2->idempotency_key);
});

test('completing checkout creates one order split by vendor with reserved stock', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingFor($country);

    $vendorA = Vendor::factory()->create();
    $warehouseA = Warehouse::factory()->create(['vendor_id' => $vendorA->id]);
    $productA = Product::factory()->create(['vendor_id' => $vendorA->id, 'price' => 1000, 'manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    app(AdjustStockAction::class)->handle($warehouseA, $productA, 10, 'seed');

    $vendorB = Vendor::factory()->create();
    $warehouseB = Warehouse::factory()->create(['vendor_id' => $vendorB->id]);
    $productB = Product::factory()->create(['vendor_id' => $vendorB->id, 'price' => 2000, 'manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    app(AdjustStockAction::class)->handle($warehouseB, $productB, 5, 'seed');

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $productA, null, 2);
    app(AddCartItemAction::class)->handle($cart, $productB, null, 1);

    $session = app(InitiateCheckoutAction::class)->handle($cart);
    $order = app(CompleteCheckoutAction::class)->handle($session, shippingDataFor($country));

    expect($order->total)->toBe(4000)
        ->and($order->status)->toBe(OrderStatus::PendingPayment)
        ->and($order->vendorOrders)->toHaveCount(2);

    $voA = $order->vendorOrders->firstWhere('vendor_id', $vendorA->id);
    $voB = $order->vendorOrders->firstWhere('vendor_id', $vendorB->id);
    expect($voA->total)->toBe(2000)
        ->and($voA->orderItems)->toHaveCount(1)
        ->and($voA->status)->toBe(VendorOrderStatus::PendingPayment)
        ->and($voB->total)->toBe(2000);

    // The customer's order-tracking timeline must never start empty.
    expect($voA->statusHistories)->toHaveCount(1)
        ->and($voA->statusHistories->first()->status)->toBe(VendorOrderStatus::PendingPayment->value);

    expect($warehouseA->stocks()->first()->reserved)->toBe(2)
        ->and($warehouseB->stocks()->first()->reserved)->toBe(1);

    expect($cart->fresh()->status->value)->toBe('converted');
    expect($order->payments)->toHaveCount(1)
        ->and($order->payments->first()->status->value)->toBe('pending')
        ->and($order->payments->first()->amount)->toBe(4000);
});

test('completing checkout sends an order confirmation to a guest by email', function () {
    Notification::fake();

    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingFor($country);
    $product = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $warehouse = Warehouse::factory()->create(['vendor_id' => $product->vendor_id]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    $session = app(InitiateCheckoutAction::class)->handle($cart);
    app(CompleteCheckoutAction::class)->handle($session, shippingDataFor($country));

    Notification::assertSentOnDemand(
        OrderConfirmationNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === 'jane@example.com',
    );
});

test('completing checkout sends an order confirmation to a registered customer', function () {
    Notification::fake();

    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingFor($country);
    $product = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $warehouse = Warehouse::factory()->create(['vendor_id' => $product->vendor_id]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    $customer = User::factory()->create();
    $cart = Cart::factory()->create(['customer_id' => $customer->id]);
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    $session = app(InitiateCheckoutAction::class)->handle($cart);
    $shippingData = shippingDataFor($country);
    $shippingData['customer_id'] = $customer->id;
    app(CompleteCheckoutAction::class)->handle($session, $shippingData);

    Notification::assertSentTo($customer, OrderConfirmationNotification::class);
});

test('replaying the same checkout session returns the same order, never creating a second one', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingFor($country);
    $product = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $warehouse = Warehouse::factory()->create(['vendor_id' => $product->vendor_id]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    $session = app(InitiateCheckoutAction::class)->handle($cart);

    $action = app(CompleteCheckoutAction::class);
    $order1 = $action->handle($session, shippingDataFor($country));
    $order2 = $action->handle($session->fresh(), shippingDataFor($country));

    expect($order2->id)->toBe($order1->id);
    expect(Order::count())->toBe(1);
});

test('a price drift since the cart was last touched is rejected without creating an order', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingFor($country);
    $product = Product::factory()->create(['price' => 1000]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    $session = app(InitiateCheckoutAction::class)->handle($cart);

    $product->update(['price' => 1500]);

    expect(fn () => app(CompleteCheckoutAction::class)->handle($session, shippingDataFor($country)))
        ->toThrow(CheckoutValidationException::class);

    expect(Order::count())->toBe(0);
});

test('insufficient stock at completion time is rejected without creating an order', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingFor($country);
    $product = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $warehouse = Warehouse::factory()->create(['vendor_id' => $product->vendor_id]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 1, 'seed');

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    $session = app(InitiateCheckoutAction::class)->handle($cart);

    // Someone else reserves the last unit between add-to-cart and checkout.
    app(ReserveStockAction::class)->handle($warehouse, $product, 1);

    expect(fn () => app(CompleteCheckoutAction::class)->handle($session, shippingDataFor($country)))
        ->toThrow(InsufficientStockException::class);

    expect(Order::count())->toBe(0);
});

test('an empty cart cannot be checked out', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingFor($country);
    $cart = Cart::factory()->create();
    $session = app(InitiateCheckoutAction::class)->handle($cart);

    expect(fn () => app(CompleteCheckoutAction::class)->handle($session, shippingDataFor($country)))
        ->toThrow(CheckoutValidationException::class);
});

test('a registered customer checkout attaches the order to their account, not as a guest', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingFor($country);
    $user = User::factory()->create();
    $product = Product::factory()->create(['manage_stock' => false]);

    $cart = Cart::factory()->create(['customer_id' => $user->id, 'session_token' => null]);
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    $session = app(InitiateCheckoutAction::class)->handle($cart);

    $shippingData = shippingDataFor($country);
    $shippingData['customer_id'] = $user->id;
    $shippingData['guest_name'] = null;
    $shippingData['guest_email'] = null;
    $shippingData['guest_phone'] = null;

    $order = app(CompleteCheckoutAction::class)->handle($session, $shippingData);

    expect($order->customer_id)->toBe($user->id)
        ->and($order->isGuestOrder())->toBeFalse();
});

test('saved-for-later items are never included in checkout', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingFor($country);
    $activeProduct = Product::factory()->create(['price' => 1000, 'manage_stock' => false]);
    $savedProduct = Product::factory()->create(['price' => 5000, 'manage_stock' => false]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $activeProduct, null, 1);
    $savedItem = app(AddCartItemAction::class)->handle($cart, $savedProduct, null, 1);
    $savedItem->update(['saved_for_later' => true]);

    $session = app(InitiateCheckoutAction::class)->handle($cart);
    $order = app(CompleteCheckoutAction::class)->handle($session, shippingDataFor($country));

    expect($order->total)->toBe(1000);
    expect($order->vendorOrders->sum(fn ($vo) => $vo->orderItems->count()))->toBe(1);
});
