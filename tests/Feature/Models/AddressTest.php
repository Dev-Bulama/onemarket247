<?php

use App\Models\Address;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;

test('ownerUserId resolves for a user-owned address', function () {
    $user = User::factory()->create();
    $address = Address::factory()->create(['addressable_type' => User::class, 'addressable_id' => $user->id]);

    expect($address->ownerUserId())->toBe($user->id);
});

test('ownerUserId resolves for a vendor-owned address', function () {
    $vendor = Vendor::factory()->create();
    $address = Address::factory()->create(['addressable_type' => Vendor::class, 'addressable_id' => $vendor->id]);

    expect($address->ownerUserId())->toBe($vendor->user_id);
});

test('ownerUserId resolves for a store-owned address via its vendor', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);
    $address = Address::factory()->create(['addressable_type' => Store::class, 'addressable_id' => $store->id]);

    expect($address->ownerUserId())->toBe($vendor->user_id);
});
