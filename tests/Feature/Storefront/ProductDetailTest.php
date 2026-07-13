<?php

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Store;
use App\Models\Vendor;

test('a published product detail page shows its details', function () {
    $product = Product::factory()->create(['price' => 2999, 'short_description' => 'A great product']);

    $response = $this->get(route('products.show', $product));

    $response->assertOk()
        ->assertSee($product->name)
        ->assertSee('29.99')
        ->assertSee('A great product');
});

test('a draft product 404s on the storefront', function () {
    $product = Product::factory()->draft()->create();

    $this->get(route('products.show', $product))->assertNotFound();
});

test('a pending approval product 404s on the storefront', function () {
    $product = Product::factory()->pendingApproval()->create();

    $this->get(route('products.show', $product))->assertNotFound();
});

test('a variable products active variations are listed with their own prices', function () {
    $product = Product::factory()->variable()->create();
    $variation = ProductVariation::factory()->create(['product_id' => $product->id, 'price' => 1500, 'is_active' => true]);
    $value = AttributeValue::factory()->create(['value' => 'Large']);
    $variation->attributeValues()->attach($value->id);

    $response = $this->get(route('products.show', $product));

    $response->assertOk()->assertSee('Large')->assertSee('15.00');
});

test('an inactive variation is not shown on the product page', function () {
    $product = Product::factory()->variable()->create();
    $inactive = ProductVariation::factory()->create(['product_id' => $product->id, 'sku' => 'INACTIVE-SKU', 'is_active' => false]);

    $this->get(route('products.show', $product))->assertOk()->assertDontSee($inactive->sku);
});

test('the product page links to its vendors store', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    $response = $this->get(route('products.show', $product));

    $response->assertOk()->assertSee($store->name);
});
