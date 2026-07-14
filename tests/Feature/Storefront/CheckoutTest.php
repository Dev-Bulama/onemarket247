<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\ReserveStockAction;
use App\Enums\ShippingRateType;
use App\Enums\StockStatus;
use App\Enums\UserType;
use App\Models\CheckoutSession;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use App\Models\User;
use App\Models\Warehouse;

/**
 * A free flat rate for the given country keeps this file's existing total
 * assertions (subtotal-only, no shipping) unchanged.
 */
function seedFreeShippingForStorefrontTest(Country $country): void
{
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'rate_type' => ShippingRateType::Free, 'base_amount' => 0]);
}

test('an empty cart redirects away from checkout', function () {
    $this->get(route('checkout.index'))->assertRedirect(route('cart.index'));
});

test('guest can complete checkout end to end and see the confirmation page', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingForStorefrontTest($country);
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $warehouse = Warehouse::factory()->create(['vendor_id' => $product->vendor_id]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    $addResponse = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 2]);
    $token = $addResponse->getCookie('cart_token')->getValue();

    $this->withCookie('cart_token', $token)->get(route('checkout.index'))->assertOk();

    $session = CheckoutSession::first();

    $storeResponse = $this->withCookie('cart_token', $token)->post(route('checkout.store'), [
        'checkout_session_key' => $session->idempotency_key,
        'email' => 'guest@example.com',
        'full_name' => 'Jane Guest',
        'phone' => '+10000000000',
        'address_line_1' => '123 Main St',
        'country_id' => $country->id,
        'postal_code' => '00000',
    ]);

    $storeResponse->assertRedirect();
    $order = Order::first();
    expect($order)->not->toBeNull();
    expect($order->total)->toBe(2000);
    expect($order->guest_email)->toBe('guest@example.com');
    expect($order->isGuestOrder())->toBeTrue();

    $this->withCookie('cart_token', $token)
        ->get($storeResponse->headers->get('Location'))
        ->assertOk()
        ->assertSee($order->order_number)
        ->assertSee('Jane Guest');
});

test('a registered customer checkout attaches to their account and is protected from other customers', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingForStorefrontTest($country);
    $user = User::factory()->create(['user_type' => UserType::Customer]);
    $product = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $warehouse = Warehouse::factory()->create(['vendor_id' => $product->vendor_id]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 5, 'seed');

    $this->actingAs($user)->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $this->actingAs($user)->get(route('checkout.index'))->assertOk();

    $session = CheckoutSession::first();

    $response = $this->actingAs($user)->post(route('checkout.store'), [
        'checkout_session_key' => $session->idempotency_key,
        'full_name' => $user->name,
        'address_line_1' => '456 Second St',
        'country_id' => $country->id,
    ]);

    $response->assertRedirect();
    $order = Order::first();
    expect($order->customer_id)->toBe($user->id);
    expect($order->guest_email)->toBeNull();

    $other = User::factory()->create(['user_type' => UserType::Customer]);
    $this->actingAs($other)->get(route('checkout.confirmation', $order))->assertForbidden();
    $this->get(route('checkout.confirmation', $order))->assertForbidden();

    $this->actingAs($user)->get(route('checkout.confirmation', $order))->assertOk();
});

test('a guest checkout requires an email address', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingForStorefrontTest($country);
    $product = Product::factory()->create();

    $addResponse = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $addResponse->getCookie('cart_token')->getValue();
    $this->withCookie('cart_token', $token)->get(route('checkout.index'));
    $session = CheckoutSession::first();

    $this->withCookie('cart_token', $token)->post(route('checkout.store'), [
        'checkout_session_key' => $session->idempotency_key,
        'full_name' => 'No Email Guest',
        'address_line_1' => '1 Main St',
        'country_id' => $country->id,
    ])->assertSessionHasErrors('email');
});

test('a price drift is rejected and sends the customer back to their cart', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingForStorefrontTest($country);
    $product = Product::factory()->create(['price' => 1000]);

    $addResponse = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $addResponse->getCookie('cart_token')->getValue();
    $this->withCookie('cart_token', $token)->get(route('checkout.index'));
    $session = CheckoutSession::first();

    $product->update(['price' => 1500]);

    $this->withCookie('cart_token', $token)->post(route('checkout.store'), [
        'checkout_session_key' => $session->idempotency_key,
        'email' => 'drift@example.com',
        'full_name' => 'Drift Guest',
        'address_line_1' => '1 Drift St',
        'country_id' => $country->id,
    ])
        ->assertRedirect(route('cart.index'))
        ->assertSessionHasErrors('checkout');

    expect(Order::count())->toBe(0);
});

test('checkout is rejected when stock runs out between add-to-cart and checkout', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingForStorefrontTest($country);
    $product = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $warehouse = Warehouse::factory()->create(['vendor_id' => $product->vendor_id]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 1, 'seed');

    $addResponse = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $addResponse->getCookie('cart_token')->getValue();
    $this->withCookie('cart_token', $token)->get(route('checkout.index'));
    $session = CheckoutSession::first();

    app(ReserveStockAction::class)->handle($warehouse, $product, 1);

    $this->withCookie('cart_token', $token)->post(route('checkout.store'), [
        'checkout_session_key' => $session->idempotency_key,
        'email' => 'outofstock@example.com',
        'full_name' => 'Out Ofstock',
        'address_line_1' => '1 Stock St',
        'country_id' => $country->id,
    ])
        ->assertRedirect(route('cart.index'))
        ->assertSessionHasErrors('checkout');

    expect(Order::count())->toBe(0);
});

test('the cart page links to checkout only when there are active items', function () {
    $this->get(route('cart.index'))->assertDontSee('Proceed to checkout');

    $product = Product::factory()->create();
    $addResponse = $this->post(route('cart.items.store'), ['product_id' => $product->id, 'quantity' => 1]);
    $token = $addResponse->getCookie('cart_token')->getValue();

    $this->withCookie('cart_token', $token)->get(route('cart.index'))->assertSee('Proceed to checkout');
});
