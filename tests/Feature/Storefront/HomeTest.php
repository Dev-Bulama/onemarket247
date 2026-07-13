<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;

test('the homepage loads with real featured content', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_featured' => true]);
    $store = Store::factory()->create(['is_featured' => true]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee($category->name)
        ->assertSee($product->name)
        ->assertSee($store->name);
});

test('the homepage does not error with no data at all', function () {
    $this->get('/')->assertOk();
});

test('the homepage only shows featured products, not every product', function () {
    $featured = Product::factory()->create(['is_featured' => true, 'name' => 'Featured Widget']);
    $notFeatured = Product::factory()->create(['is_featured' => false, 'name' => 'Ordinary Widget']);

    $this->get('/')->assertOk()->assertSee($featured->name)->assertDontSee($notFeatured->name);
});
