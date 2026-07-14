<?php

use App\Actions\Cart\AddCartItemAction;
use App\Actions\Checkout\CompleteCheckoutAction;
use App\Actions\Checkout\InitiateCheckoutAction;
use App\Actions\Tax\CalculateTaxAction;
use App\Actions\Tax\ResolveTaxRateAction;
use App\Enums\ShippingRateType;
use App\Enums\StockStatus;
use App\Models\Cart;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use App\Models\State;
use App\Models\TaxClass;
use App\Models\TaxRate;
use App\Models\Vendor;

function seedFreeShippingForTaxSuite(Country $country): void
{
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'rate_type' => ShippingRateType::Free, 'base_amount' => 0]);
}

test('resolving a tax rate returns null when nothing matches, never throws', function () {
    $country = Country::factory()->create();

    $rate = app(ResolveTaxRateAction::class)->handle(null, $country->id, null, null, null);

    expect($rate)->toBeNull();

    $result = app(CalculateTaxAction::class)->handle(10000, null, $country->id, null, null, null);

    expect($result['rate'])->toBeNull()
        ->and($result['taxAmount'])->toBe(0);
});

test('location specificity is preferred from postal code down to country', function () {
    $country = Country::factory()->create();
    $state = State::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['state_id' => $state->id, 'country_id' => $country->id]);

    $countryRate = TaxRate::factory()->create(['country_id' => $country->id, 'state_id' => null, 'city_id' => null, 'postal_code' => null, 'rate_percent' => 5]);
    $stateRate = TaxRate::factory()->create(['country_id' => $country->id, 'state_id' => $state->id, 'city_id' => null, 'postal_code' => null, 'rate_percent' => 8]);
    $cityRate = TaxRate::factory()->create(['country_id' => $country->id, 'state_id' => $state->id, 'city_id' => $city->id, 'postal_code' => null, 'rate_percent' => 10]);
    $postalRate = TaxRate::factory()->create(['country_id' => $country->id, 'postal_code' => '90210', 'rate_percent' => 12]);

    expect(app(ResolveTaxRateAction::class)->handle(null, $country->id, null, null, null)->id)->toBe($countryRate->id)
        ->and(app(ResolveTaxRateAction::class)->handle(null, $country->id, $state->id, null, null)->id)->toBe($stateRate->id)
        ->and(app(ResolveTaxRateAction::class)->handle(null, $country->id, $state->id, $city->id, null)->id)->toBe($cityRate->id)
        ->and(app(ResolveTaxRateAction::class)->handle(null, $country->id, $state->id, $city->id, '90210')->id)->toBe($postalRate->id);
});

test('a tax-class-specific rate is preferred over a general rate at the same location tier', function () {
    $country = Country::factory()->create();
    $taxClass = TaxClass::factory()->create();

    $generalRate = TaxRate::factory()->create(['tax_class_id' => null, 'country_id' => $country->id, 'rate_percent' => 5]);
    $classRate = TaxRate::factory()->create(['tax_class_id' => $taxClass->id, 'country_id' => $country->id, 'rate_percent' => 15]);

    expect(app(ResolveTaxRateAction::class)->handle($taxClass->id, $country->id, null, null, null)->id)->toBe($classRate->id)
        ->and(app(ResolveTaxRateAction::class)->handle(null, $country->id, null, null, null)->id)->toBe($generalRate->id);
});

test('a product falls back to the general rate when no rate exists for its own tax class', function () {
    $country = Country::factory()->create();
    $taxClass = TaxClass::factory()->create();
    $generalRate = TaxRate::factory()->create(['tax_class_id' => null, 'country_id' => $country->id, 'rate_percent' => 7]);

    $rate = app(ResolveTaxRateAction::class)->handle($taxClass->id, $country->id, null, null, null);

    expect($rate->id)->toBe($generalRate->id);
});

test('computeTax rounds to the nearest minor unit', function () {
    $rate = TaxRate::factory()->create(['rate_percent' => 7.5]);

    expect($rate->computeTax(1000))->toBe(75)
        ->and($rate->computeTax(999))->toBe(75);
});

test('checkout computes real per-item tax, rolls it into order and vendor order totals, and snapshots it', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingForTaxSuite($country);

    $taxClass = TaxClass::factory()->create();
    TaxRate::factory()->create(['country_id' => $country->id, 'tax_class_id' => null, 'rate_percent' => 10]);
    TaxRate::factory()->create(['country_id' => $country->id, 'tax_class_id' => $taxClass->id, 'rate_percent' => 20]);

    $vendor = Vendor::factory()->create();
    $productTaxed = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 1000, 'tax_class_id' => $taxClass->id, 'manage_stock' => false, 'stock_status' => StockStatus::InStock]);
    $productGeneral = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 2000, 'tax_class_id' => null, 'manage_stock' => false, 'stock_status' => StockStatus::InStock]);

    $cart = Cart::factory()->create();
    app(AddCartItemAction::class)->handle($cart, $productTaxed, null, 1);
    app(AddCartItemAction::class)->handle($cart, $productGeneral, null, 1);

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

    // 1000 * 20% + 2000 * 10% = 200 + 200 = 400
    expect($order->tax_amount)->toBe(400)
        ->and($order->total)->toBe(1000 + 2000 + 400)
        ->and($order->vendorOrders)->toHaveCount(1);

    $vendorOrder = $order->vendorOrders->first();
    expect($vendorOrder->tax_amount)->toBe(400);

    $taxedItem = $vendorOrder->orderItems()->whereHas('product', fn ($q) => $q->whereKey($productTaxed->id))->first();
    $generalItem = $vendorOrder->orderItems()->whereHas('product', fn ($q) => $q->whereKey($productGeneral->id))->first();

    expect($taxedItem->taxSnapshot->tax_amount)->toBe(200)
        ->and((float) $taxedItem->taxSnapshot->rate_percent)->toBe(20.0)
        ->and($taxedItem->taxSnapshot->taxable_amount)->toBe(1000)
        ->and($generalItem->taxSnapshot->tax_amount)->toBe(200)
        ->and((float) $generalItem->taxSnapshot->rate_percent)->toBe(10.0);
});

test('checkout still succeeds with zero tax when no tax rate is configured for the destination', function () {
    Currency::factory()->create(['is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingForTaxSuite($country);

    $product = Product::factory()->create(['price' => 1500, 'manage_stock' => false, 'stock_status' => StockStatus::InStock]);

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

    expect($order->tax_amount)->toBe(0)
        ->and($order->total)->toBe(1500);

    $item = $order->vendorOrders->first()->orderItems()->first();
    expect($item->taxSnapshot->tax_amount)->toBe(0)
        ->and($item->taxSnapshot->tax_rate_id)->toBeNull();
});
