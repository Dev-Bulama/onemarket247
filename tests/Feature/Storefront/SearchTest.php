<?php

use App\Enums\SpotlightDisplayArea;
use App\Models\Product;
use App\Models\ProductSpotlight;

test('search finds products by name', function () {
    $match = Product::factory()->create(['name' => 'Wireless Bluetooth Headphones']);
    $noMatch = Product::factory()->create(['name' => 'Garden Hose']);

    $response = $this->get('/search?q=Bluetooth');

    $response->assertOk()->assertSee($match->name)->assertDontSee($noMatch->name);
});

test('search finds products by sku', function () {
    $match = Product::factory()->create(['sku' => 'UNIQUE-SKU-123']);

    $this->get('/search?q=UNIQUE-SKU-123')->assertOk()->assertSee($match->name);
});

test('search with no query shows a prompt instead of every product', function () {
    $product = Product::factory()->create();

    $response = $this->get('/search');

    $response->assertOk()->assertSee('Enter a search term')->assertDontSee($product->name);
});

test('an empty result set is communicated, not a blank page', function () {
    $this->get('/search?q=nonexistentxyz')->assertOk()->assertSee('No products match');
});

test('a search-area spotlight product is shown above the search results', function () {
    $spotlighted = Product::factory()->create(['name' => 'Sponsored Widget']);
    ProductSpotlight::factory()->forArea(SpotlightDisplayArea::Search)->create(['product_id' => $spotlighted->id]);
    $match = Product::factory()->create(['name' => 'Wireless Bluetooth Headphones']);

    $response = $this->get('/search?q=Bluetooth');

    $response->assertOk()->assertSeeInOrder(['Spotlight', $spotlighted->name, $match->name]);
});
