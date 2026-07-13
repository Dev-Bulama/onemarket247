<?php

use App\Models\Brand;
use App\Models\Product;

test('the brand index page lists active brands', function () {
    $brand = Brand::factory()->create(['is_active' => true]);

    $this->get('/brands')->assertOk()->assertSee($brand->name);
});

test('a brand page lists only that brands products', function () {
    $brandA = Brand::factory()->create();
    $brandB = Brand::factory()->create();
    $productA = Product::factory()->create(['brand_id' => $brandA->id]);
    $productB = Product::factory()->create(['brand_id' => $brandB->id]);

    $response = $this->get(route('brands.show', $brandA));

    $response->assertOk()->assertSee($productA->name)->assertDontSee($productB->name);
});
