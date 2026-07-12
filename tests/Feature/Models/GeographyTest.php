<?php

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\QueryException;

test('a country has many states and cities', function () {
    $country = Country::factory()->create();
    $state = State::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['country_id' => $country->id, 'state_id' => $state->id]);

    expect($country->states)->toHaveCount(1)
        ->and($country->states->first()->id)->toBe($state->id)
        ->and($country->cities)->toHaveCount(1)
        ->and($state->cities->first()->id)->toBe($city->id)
        ->and($city->state->id)->toBe($state->id)
        ->and($city->country->id)->toBe($country->id);
});

test('country iso2 must be unique', function () {
    Country::factory()->create(['iso2' => 'ZZ']);

    expect(fn () => Country::factory()->create(['iso2' => 'ZZ']))
        ->toThrow(QueryException::class);
});
