<?php

use App\Models\City;
use App\Models\Country;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use App\Models\State;

test('switching delivery location persists it in session', function () {
    $country = Country::factory()->create();
    $state = State::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['state_id' => $state->id, 'country_id' => $country->id]);

    $this->post(route('location.switch'), [
        'country_id' => $country->id,
        'state_id' => $state->id,
        'city_id' => $city->id,
    ])->assertRedirect();

    expect(session('delivery_location'))->toBe([
        'country_id' => $country->id,
        'state_id' => $state->id,
        'city_id' => $city->id,
    ]);
});

test('an inactive country is rejected', function () {
    $country = Country::factory()->create(['is_active' => false]);

    $this->post(route('location.switch'), ['country_id' => $country->id])->assertNotFound();
});

test('a state that does not belong to the given country is rejected', function () {
    $country = Country::factory()->create();
    $otherCountry = Country::factory()->create();
    $state = State::factory()->create(['country_id' => $otherCountry->id]);

    $this->post(route('location.switch'), ['country_id' => $country->id, 'state_id' => $state->id])->assertNotFound();
});

test('the header shows a deliverable location when a shipping zone covers it', function () {
    $country = Country::factory()->create();
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id]);

    session(['delivery_location' => ['country_id' => $country->id, 'state_id' => null, 'city_id' => null]]);

    $this->get('/')->assertOk()->assertDontSee('not deliverable');
});

test('the header flags a location with no matching shipping zone as not deliverable', function () {
    $country = Country::factory()->create();

    session(['delivery_location' => ['country_id' => $country->id, 'state_id' => null, 'city_id' => null]]);

    $this->get('/')->assertOk()->assertSee('not deliverable');
});
