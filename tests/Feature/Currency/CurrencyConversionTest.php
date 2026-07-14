<?php

use App\Actions\Cart\AddCartItemAction;
use App\Actions\Checkout\CompleteCheckoutAction;
use App\Actions\Checkout\InitiateCheckoutAction;
use App\Actions\Currency\ConvertCurrencyAction;
use App\Enums\ShippingRateType;
use App\Enums\StockStatus;
use App\Models\Cart;
use App\Models\Country;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;

test('converting between the same currency is a no-op', function () {
    $usd = Currency::factory()->create(['decimal_places' => 2]);

    expect(app(ConvertCurrencyAction::class)->handle(1000, $usd, $usd))->toBe(1000);
});

test('converting via the default-currency pivot accounts for differing decimal places', function () {
    $usd = Currency::factory()->create(['decimal_places' => 2]);
    ExchangeRate::factory()->create(['currency_id' => $usd->id, 'rate' => 1]);

    $jpy = Currency::factory()->create(['decimal_places' => 0]);
    ExchangeRate::factory()->create(['currency_id' => $jpy->id, 'rate' => 150]);

    // $10.00 (1000 minor units, 2dp) -> 1500 JPY (1500 minor units, 0dp)
    expect(app(ConvertCurrencyAction::class)->handle(1000, $usd, $jpy))->toBe(1500);
});

test('converting is symmetric back through the default currency', function () {
    $usd = Currency::factory()->create(['decimal_places' => 2]);
    ExchangeRate::factory()->create(['currency_id' => $usd->id, 'rate' => 1]);

    $eur = Currency::factory()->create(['decimal_places' => 2]);
    ExchangeRate::factory()->create(['currency_id' => $eur->id, 'rate' => 0.92]);

    $converted = app(ConvertCurrencyAction::class)->handle(10000, $usd, $eur);
    expect($converted)->toBe(9200);

    $back = app(ConvertCurrencyAction::class)->handle($converted, $eur, $usd);
    expect($back)->toBe(10000);
});

test('a currency with no exchange rate row is treated as rate 1', function () {
    $usd = Currency::factory()->create(['decimal_places' => 2]);
    ExchangeRate::factory()->create(['currency_id' => $usd->id, 'rate' => 1]);

    $noRate = Currency::factory()->create(['decimal_places' => 2]);

    expect(app(ConvertCurrencyAction::class)->handle(1000, $usd, $noRate))->toBe(1000);
});

test('checkout snapshots the exchange rate in effect at the time of the order', function () {
    $usd = Currency::factory()->create(['is_default' => true]);
    ExchangeRate::factory()->create(['currency_id' => $usd->id, 'rate' => 1.5]);

    $country = Country::factory()->create();
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'rate_type' => ShippingRateType::Free, 'base_amount' => 0]);

    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => false, 'stock_status' => StockStatus::InStock]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $product, null, 1);

    $session = app(InitiateCheckoutAction::class)->handle($cart);
    $order = app(CompleteCheckoutAction::class)->handle($session, [
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
    ]);

    expect((float) $order->exchange_rate_snapshot)->toBe(1.5);
});
