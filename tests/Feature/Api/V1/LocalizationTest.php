<?php

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductTranslation;

test('the X-Language header selects the product name translation', function () {
    Currency::factory()->create(['is_default' => true]);
    $french = Language::factory()->create(['code' => 'fr']);
    $product = Product::factory()->create(['name' => 'English Name']);
    ProductTranslation::factory()->create(['product_id' => $product->id, 'language_id' => $french->id, 'name' => 'Nom Français']);

    $this->withHeader('X-Language', 'fr')
        ->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Nom Français');
});

test('an unknown X-Language header falls back to the default language rather than erroring', function () {
    Currency::factory()->create(['is_default' => true]);
    Language::factory()->create(['code' => 'en', 'is_default' => true]);
    $product = Product::factory()->create(['name' => 'English Name']);

    $this->withHeader('X-Language', 'zz')
        ->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.name', 'English Name');
});

test('the X-Currency header changes the formatted price without changing the underlying base-currency amount', function () {
    Currency::factory()->create(['code' => 'USD', 'is_default' => true, 'symbol' => '$', 'decimal_places' => 2]);
    $ngn = Currency::factory()->create(['code' => 'NGN', 'symbol' => '₦', 'decimal_places' => 2]);
    ExchangeRate::factory()->create(['currency_id' => $ngn->id, 'rate' => 1500]);
    $product = Product::factory()->create(['price' => 1000]);

    $usdResponse = $this->getJson("/api/v1/products/{$product->slug}")->assertOk();
    $ngnResponse = $this->withHeader('X-Currency', 'NGN')->getJson("/api/v1/products/{$product->slug}")->assertOk();

    // `amount` and `currency` stay pinned to the base currency regardless
    // of display preference — they're for client-side math, not display;
    // only `formatted` (which shoppers actually see) changes.
    expect($usdResponse->json('data.price.currency'))->toBe('USD')
        ->and($ngnResponse->json('data.price.currency'))->toBe('USD')
        ->and($ngnResponse->json('data.price.amount'))->toBe($usdResponse->json('data.price.amount'))
        ->and($ngnResponse->json('data.price.formatted'))->not->toBe($usdResponse->json('data.price.formatted'))
        ->and($ngnResponse->json('data.price.formatted'))->toContain('₦');
});
