<?php

use App\Actions\Shipping\CalculateShippingCostAction;
use App\Actions\Shipping\ResolveShippingZoneAction;
use App\Enums\ShippingRateType;
use App\Exceptions\ShippingUnavailableException;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Models\ShippingClass;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use App\Models\State;

test('a city-level zone location wins over a state-level and a country-level one', function () {
    $country = Country::factory()->create();
    $state = State::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['state_id' => $state->id]);

    $countryZone = ShippingZone::factory()->create(['name' => 'Country zone']);
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $countryZone->id, 'country_id' => $country->id, 'state_id' => null, 'city_id' => null]);

    $stateZone = ShippingZone::factory()->create(['name' => 'State zone']);
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $stateZone->id, 'country_id' => $country->id, 'state_id' => $state->id, 'city_id' => null]);

    $cityZone = ShippingZone::factory()->create(['name' => 'City zone']);
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $cityZone->id, 'country_id' => $country->id, 'state_id' => $state->id, 'city_id' => $city->id]);

    $resolved = app(ResolveShippingZoneAction::class)->handle($country->id, $state->id, $city->id);

    expect($resolved->id)->toBe($cityZone->id);
});

test('a state-level zone location wins over a country-level one when no city matches', function () {
    $country = Country::factory()->create();
    $state = State::factory()->create(['country_id' => $country->id]);

    $countryZone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $countryZone->id, 'country_id' => $country->id, 'state_id' => null, 'city_id' => null]);

    $stateZone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $stateZone->id, 'country_id' => $country->id, 'state_id' => $state->id, 'city_id' => null]);

    $resolved = app(ResolveShippingZoneAction::class)->handle($country->id, $state->id, null);

    expect($resolved->id)->toBe($stateZone->id);
});

test('an inactive zone is never resolved even if its location matches', function () {
    $country = Country::factory()->create();
    $zone = ShippingZone::factory()->create(['is_active' => false]);
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);

    $resolved = app(ResolveShippingZoneAction::class)->handle($country->id, null, null);

    expect($resolved)->toBeNull();
});

test('a zone with no locations at all is used as the rest-of-world fallback', function () {
    $country = Country::factory()->create();
    $catchAll = ShippingZone::factory()->create();

    $resolved = app(ResolveShippingZoneAction::class)->handle($country->id, null, null);

    expect($resolved->id)->toBe($catchAll->id);
});

test('calculating cost prefers a rate scoped to the shared shipping class over the general rate', function () {
    $country = Country::factory()->create();
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    $class = ShippingClass::factory()->create();

    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'shipping_class_id' => null, 'base_amount' => 100]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'shipping_class_id' => $class->id, 'base_amount' => 700]);

    $product = Product::factory()->create(['shipping_class_id' => $class->id]);
    $lines = collect([['sellable' => $product, 'quantity' => 1]]);

    $cost = app(CalculateShippingCostAction::class)->handle($lines, 10000, $country->id, null, null);

    expect($cost)->toBe(700);
});

test('calculating cost falls back to the general rate when line items span more than one shipping class', function () {
    $country = Country::factory()->create();
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    $classA = ShippingClass::factory()->create();
    $classB = ShippingClass::factory()->create();

    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'shipping_class_id' => null, 'base_amount' => 400]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'shipping_class_id' => $classA->id, 'base_amount' => 900]);

    $productA = Product::factory()->create(['shipping_class_id' => $classA->id]);
    $productB = Product::factory()->create(['shipping_class_id' => $classB->id]);
    $lines = collect([
        ['sellable' => $productA, 'quantity' => 1],
        ['sellable' => $productB, 'quantity' => 1],
    ]);

    $cost = app(CalculateShippingCostAction::class)->handle($lines, 10000, $country->id, null, null);

    expect($cost)->toBe(400);
});

test('a per-weight rate charges base plus per-kg times total weight', function () {
    $country = Country::factory()->create();
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create([
        'shipping_zone_id' => $zone->id,
        'rate_type' => ShippingRateType::PerWeight,
        'base_amount' => 200,
        'per_kg_amount' => 150,
    ]);

    $product = Product::factory()->create(['weight' => 2.5]);
    $lines = collect([['sellable' => $product, 'quantity' => 2]]);

    $cost = app(CalculateShippingCostAction::class)->handle($lines, 10000, $country->id, null, null);

    expect($cost)->toBe(200 + (int) ceil(5.0 * 150));
});

test('a free-shipping threshold overrides a flat rate once the subtotal is met', function () {
    $country = Country::factory()->create();
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create([
        'shipping_zone_id' => $zone->id,
        'rate_type' => ShippingRateType::Flat,
        'base_amount' => 800,
        'free_shipping_min_amount' => 5000,
    ]);

    $product = Product::factory()->create();
    $lines = collect([['sellable' => $product, 'quantity' => 1]]);

    expect(app(CalculateShippingCostAction::class)->handle($lines, 4999, $country->id, null, null))->toBe(800);
    expect(app(CalculateShippingCostAction::class)->handle($lines, 5000, $country->id, null, null))->toBe(0);
});

test('shipping is rejected with a clear exception when no zone at all covers the destination', function () {
    $country = Country::factory()->create();
    $product = Product::factory()->create();
    $lines = collect([['sellable' => $product, 'quantity' => 1]]);

    expect(fn () => app(CalculateShippingCostAction::class)->handle($lines, 1000, $country->id, null, null))
        ->toThrow(ShippingUnavailableException::class);
});

test('shipping is rejected when a zone matches but has no rate configured', function () {
    $country = Country::factory()->create();
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);

    $product = Product::factory()->create();
    $lines = collect([['sellable' => $product, 'quantity' => 1]]);

    expect(fn () => app(CalculateShippingCostAction::class)->handle($lines, 1000, $country->id, null, null))
        ->toThrow(ShippingUnavailableException::class);
});
