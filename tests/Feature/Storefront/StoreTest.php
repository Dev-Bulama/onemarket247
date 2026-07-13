<?php

use App\Enums\StoreStatus;
use App\Models\Product;
use App\Models\Store;
use App\Models\Vendor;

test('the store index page lists active stores', function () {
    $active = Store::factory()->create(['status' => StoreStatus::Active]);
    $inactive = Store::factory()->create(['status' => StoreStatus::Inactive]);

    $response = $this->get('/stores');

    $response->assertOk()->assertSee($active->name)->assertDontSee($inactive->name);
});

test('the store index page supports searching by name', function () {
    $store = Store::factory()->create(['name' => 'Acme Gadgets']);
    $other = Store::factory()->create(['name' => 'Zephyr Goods']);

    $response = $this->get('/stores?q=Acme');

    $response->assertOk()->assertSee($store->name)->assertDontSee($other->name);
});

test('a store page lists its own real published products, not another vendors', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create(['status' => StoreStatus::Active]);
    $ownProduct = Product::factory()->create(['vendor_id' => $vendor->id]);

    $otherVendor = Vendor::factory()->create();
    $otherProduct = Product::factory()->create(['vendor_id' => $otherVendor->id]);

    $response = $this->get(route('stores.show', $store->slug));

    $response->assertOk()
        ->assertSee($ownProduct->name)
        ->assertDontSee($otherProduct->name);
});

test('an inactive store 404s', function () {
    $store = Store::factory()->create(['status' => StoreStatus::Inactive]);

    $this->get(route('stores.show', $store->slug))->assertNotFound();
});
