<?php

use App\Enums\StoreStaffStatus;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use Spatie\Permission\Models\Permission;

test('the owning vendor can update their store', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);

    expect($vendor->user->can('update', $store))->toBeTrue();
});

test('an active store staff member with the right permission can update the store', function () {
    Permission::findOrCreate('store.settings.manage', 'web');

    $store = Store::factory()->create();
    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo('store.settings.manage');
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('update', $store))->toBeTrue();
});

test('a suspended store staff member cannot update the store even with the permission', function () {
    Permission::findOrCreate('store.settings.manage', 'web');

    $store = Store::factory()->create();
    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo('store.settings.manage');
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Suspended,
    ]);

    expect($staffUser->can('update', $store))->toBeFalse();
});

test('an unrelated user cannot update a store', function () {
    $store = Store::factory()->create();
    $stranger = User::factory()->create();

    expect($stranger->can('update', $store))->toBeFalse();
});

test('viewing a store whose vendor has been deleted does not crash', function () {
    Permission::findOrCreate('stores.manage', 'web');

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);
    $vendor->delete();

    $admin = User::factory()->create();
    $admin->givePermissionTo('stores.manage');

    expect($store->fresh()->vendor)->toBeNull()
        ->and($admin->can('view', $store->fresh()))->toBeTrue()
        ->and($vendor->user->can('view', $store->fresh()))->toBeFalse();
});
