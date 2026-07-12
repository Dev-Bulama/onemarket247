<?php

use App\Enums\StoreStatus;
use App\Models\Store;
use App\Models\Vendor;

test('an active store is publicly visible by slug', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create(['name' => 'Acme Supplies', 'status' => StoreStatus::Active]);

    $this->get('/stores/'.$store->slug)
        ->assertOk()
        ->assertSee('Acme Supplies');
});

test('a store on vacation is still publicly visible with its vacation message', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create([
        'status' => StoreStatus::Vacation,
        'vacation_message' => 'Back in two weeks!',
    ]);

    $this->get('/stores/'.$store->slug)
        ->assertOk()
        ->assertSee('Back in two weeks!');
});

test('an inactive store is not publicly reachable', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create(['status' => StoreStatus::Inactive]);

    $this->get('/stores/'.$store->slug)->assertNotFound();
});

test('an unknown store slug 404s', function () {
    $this->get('/stores/does-not-exist')->assertNotFound();
});
