<?php

use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\PaymentGateway;
use App\Models\State;

test('config reports the default currency, language, and available payment methods', function () {
    Currency::factory()->create(['code' => 'NGN', 'is_default' => true]);
    Language::factory()->create(['code' => 'en', 'is_default' => true]);

    $response = $this->getJson('/api/v1/config')->assertOk();

    $response->assertJsonPath('data.default_currency', 'NGN')
        ->assertJsonPath('data.default_language', 'en')
        ->assertJsonPath('data.payment_methods', ['bank_transfer']);
});

test('config includes paystack once an active gateway is configured', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'is_active' => true, 'secret_key' => 'sk_test']);

    $this->getJson('/api/v1/config')
        ->assertOk()
        ->assertJsonPath('data.payment_methods', ['bank_transfer', 'paystack']);
});

test('languages endpoint lists only active languages', function () {
    Language::factory()->create(['name' => 'English', 'is_active' => true]);
    Language::factory()->create(['name' => 'Hidden', 'is_active' => false]);

    $this->getJson('/api/v1/languages')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'English');
});

test('currencies endpoint lists only active currencies', function () {
    Currency::factory()->create(['code' => 'NGN', 'is_active' => true]);
    Currency::factory()->create(['code' => 'XXX', 'is_active' => false]);

    $this->getJson('/api/v1/currencies')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('the location chain — countries, states, cities — resolves correctly', function () {
    $country = Country::factory()->create(['is_active' => true]);
    $state = State::factory()->create(['country_id' => $country->id, 'is_active' => true]);
    City::factory()->create(['country_id' => $country->id, 'state_id' => $state->id, 'is_active' => true]);

    $this->getJson('/api/v1/countries')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/countries/{$country->id}/states")->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/states/{$state->id}/cities")->assertOk()->assertJsonCount(1, 'data');
});
