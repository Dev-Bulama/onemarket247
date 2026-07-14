<?php

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Language;
use App\Models\Product;
use App\Support\PriceDisplay;

it('sets the default locale and ltr direction when no session choice exists', function () {
    Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true, 'direction' => 'ltr']);
    Language::factory()->create(['code' => 'ar', 'is_default' => false, 'is_active' => true, 'direction' => 'rtl']);

    $this->get('/')->assertOk()->assertSee('dir="ltr"', false);

    expect(app()->getLocale())->toBe('en');
});

it('switches locale and rtl direction based on the session choice', function () {
    Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true, 'direction' => 'ltr']);
    Language::factory()->create(['code' => 'ar', 'is_default' => false, 'is_active' => true, 'direction' => 'rtl']);

    $this->withSession(['locale' => 'ar'])->get('/')->assertOk()->assertSee('dir="rtl"', false);

    expect(app()->getLocale())->toBe('ar');
});

it('falls back to any active language if no default and no session choice exist', function () {
    Language::factory()->create(['code' => 'fr', 'is_default' => false, 'is_active' => true]);

    $this->get('/')->assertOk();

    expect(app()->getLocale())->toBe('fr');
});

it('switches locale via the locale switch route and persists it in session', function () {
    Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);
    Language::factory()->create(['code' => 'fr', 'is_default' => false, 'is_active' => true]);

    $this->post('/locale/fr')->assertRedirect();

    expect(session('locale'))->toBe('fr');
});

it('cannot switch locale to an inactive or unknown language code', function () {
    Language::factory()->create(['code' => 'en', 'is_default' => true, 'is_active' => true]);
    Language::factory()->create(['code' => 'de', 'is_active' => false]);

    $this->post('/locale/de')->assertNotFound();
    $this->post('/locale/xx')->assertNotFound();
});

it('formats a price in the default currency when no display currency is selected', function () {
    Currency::factory()->create(['code' => 'USD', 'is_default' => true, 'is_active' => true, 'symbol' => '$', 'symbol_position' => 'before', 'decimal_places' => 2]);

    $this->get('/');

    expect(PriceDisplay::format(1050))->toBe('$10.50');
});

it('converts a price into the session-selected display currency', function () {
    $usd = Currency::factory()->create(['code' => 'USD', 'is_default' => true, 'is_active' => true, 'symbol' => '$', 'symbol_position' => 'before', 'decimal_places' => 2]);
    ExchangeRate::factory()->create(['currency_id' => $usd->id, 'rate' => 1]);

    $jpy = Currency::factory()->create(['code' => 'JPY', 'is_active' => true, 'symbol' => '¥', 'symbol_position' => 'before', 'decimal_places' => 0]);
    ExchangeRate::factory()->create(['currency_id' => $jpy->id, 'rate' => 150]);

    $this->withSession(['display_currency' => 'JPY'])->get('/');

    expect(PriceDisplay::format(1000))->toBe('¥1,500');
});

it('switches currency via the currency switch route and persists it in session', function () {
    Currency::factory()->create(['code' => 'USD', 'is_default' => true, 'is_active' => true]);
    Currency::factory()->create(['code' => 'NGN', 'is_default' => false, 'is_active' => true]);

    $this->post('/currency/NGN')->assertRedirect();

    expect(session('display_currency'))->toBe('NGN');
});

it('cannot switch to an inactive or unknown currency code', function () {
    Currency::factory()->create(['code' => 'USD', 'is_default' => true, 'is_active' => true]);
    Currency::factory()->create(['code' => 'XXX', 'is_active' => false]);

    $this->post('/currency/XXX')->assertNotFound();
    $this->post('/currency/ZZZ')->assertNotFound();
});

it('renders a shop listing page price using the price directive', function () {
    Currency::factory()->create(['code' => 'USD', 'is_default' => true, 'is_active' => true, 'symbol' => '$', 'symbol_position' => 'before', 'decimal_places' => 2]);

    Product::factory()->create(['price' => 2599, 'status' => 'published']);

    $this->get(route('shop.index'))->assertOk()->assertSee('$25.99');
});
