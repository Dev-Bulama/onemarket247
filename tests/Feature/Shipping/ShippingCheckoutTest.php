<?php

use App\Actions\Cart\AddCartItemAction;
use App\Actions\Checkout\CompleteCheckoutAction;
use App\Actions\Checkout\InitiateCheckoutAction;
use App\Enums\ShippingRateType;
use App\Exceptions\CheckoutValidationException;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;

function shippingDataForCountry(Country $country): array
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

test('checkout computes a real per-vendor shipping cost from a configured zone/rate and adds it to both totals', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();

    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'rate_type' => ShippingRateType::Flat, 'base_amount' => 750]);

    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => false]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $product, null, 2);
    $session = app(InitiateCheckoutAction::class)->handle($cart);

    $order = app(CompleteCheckoutAction::class)->handle($session, shippingDataForCountry($country));

    expect($order->shipping_amount)->toBe(750)
        ->and($order->total)->toBe(2000 + 750)
        ->and($order->vendorOrders->first()->shipping_amount)->toBe(750)
        ->and($order->vendorOrders->first()->total)->toBe(2000 + 750);
});

test('a multi-vendor checkout charges each vendor order its own shipping cost', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();

    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'rate_type' => ShippingRateType::Flat, 'base_amount' => 300]);

    $productA = Product::factory()->create(['price' => 1000, 'manage_stock' => false]);
    $productB = Product::factory()->create(['price' => 2000, 'manage_stock' => false]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $productA, null, 1);
    app(AddCartItemAction::class)->handle($cart, $productB, null, 1);
    $session = app(InitiateCheckoutAction::class)->handle($cart);

    $order = app(CompleteCheckoutAction::class)->handle($session, shippingDataForCountry($country));

    expect($order->vendorOrders)->toHaveCount(2);
    expect($order->vendorOrders->every(fn ($vendorOrder) => $vendorOrder->shipping_amount === 300))->toBeTrue();
    expect($order->shipping_amount)->toBe(600);
});

test('checkout is rejected with a clear message when no shipping zone covers the destination', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    $product = Product::factory()->create(['manage_stock' => false]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    $session = app(InitiateCheckoutAction::class)->handle($cart);

    expect(fn () => app(CompleteCheckoutAction::class)->handle($session, shippingDataForCountry($country)))
        ->toThrow(CheckoutValidationException::class);

    expect(Order::count())->toBe(0);
});

test('a rest-of-world catch-all zone with no locations is used when no specific zone matches the destination', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();

    $catchAll = ShippingZone::factory()->create(['name' => 'Rest of World']);
    ShippingRate::factory()->create(['shipping_zone_id' => $catchAll->id, 'rate_type' => ShippingRateType::Flat, 'base_amount' => 999]);

    $product = Product::factory()->create(['price' => 500, 'manage_stock' => false]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    $session = app(InitiateCheckoutAction::class)->handle($cart);

    $order = app(CompleteCheckoutAction::class)->handle($session, shippingDataForCountry($country));

    expect($order->shipping_amount)->toBe(999);
});

test('a per-weight rate and a free-shipping threshold both apply correctly at checkout', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();

    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create([
        'shipping_zone_id' => $zone->id,
        'rate_type' => ShippingRateType::PerWeight,
        'base_amount' => 100,
        'per_kg_amount' => 200,
        'free_shipping_min_amount' => 100000,
    ]);

    $product = Product::factory()->create(['price' => 1000, 'weight' => 3, 'manage_stock' => false]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);
    $session = app(InitiateCheckoutAction::class)->handle($cart);

    $order = app(CompleteCheckoutAction::class)->handle($session, shippingDataForCountry($country));

    expect($order->shipping_amount)->toBe(100 + 3 * 200);
});
