<?php

use App\Models\Brand;
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

test('the homepage new arrivals row includes any published product, not just featured ones', function () {
    $featured = Product::factory()->create(['is_featured' => true, 'name' => 'Featured Widget']);
    $notFeatured = Product::factory()->create(['is_featured' => false, 'name' => 'Ordinary Widget']);

    $this->get('/')->assertOk()->assertSee($featured->name)->assertSee($notFeatured->name);
});

test('the homepage never shows a draft product', function () {
    $draft = Product::factory()->draft()->create(['name' => 'Secret Draft Widget']);

    $this->get('/')->assertOk()->assertDontSee($draft->name);
});

test('the homepage shows active brands but not inactive ones', function () {
    $active = Brand::factory()->create(['is_active' => true, 'name' => 'Active Brand']);
    $inactive = Brand::factory()->create(['is_active' => false, 'name' => 'Inactive Brand']);

    $this->get('/')->assertOk()->assertSee($active->name)->assertDontSee($inactive->name);
});
