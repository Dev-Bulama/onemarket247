<?php

use App\Enums\StoreStaffStatus;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use Spatie\Permission\Models\Permission;

test('the owning vendor can update and delete their own product', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->create();

    expect($vendor->user->can('update', $product))->toBeTrue()
        ->and($vendor->user->can('delete', $product))->toBeTrue()
        ->and($vendor->user->can('create', Product::class))->toBeTrue();
});

test('an active store staff member with the right permission can update the product', function () {
    Permission::findOrCreate('store.products.manage', 'web');

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->create();

    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo('store.products.manage');
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('update', $product))->toBeTrue()
        ->and($staffUser->can('create', Product::class))->toBeTrue();
});

test('a store staff member without the permission cannot update the product', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->create();

    $staffUser = User::factory()->create();
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('update', $product))->toBeFalse();
});

test('a suspended store staff member cannot update the product even with the permission', function () {
    Permission::findOrCreate('store.products.manage', 'web');

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->create();

    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo('store.products.manage');
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Suspended,
    ]);

    expect($staffUser->can('update', $product))->toBeFalse();
});

test('an unrelated vendor cannot update or delete another vendors product', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->create();

    $otherVendor = Vendor::factory()->create();

    expect($otherVendor->user->can('update', $product))->toBeFalse()
        ->and($otherVendor->user->can('delete', $product))->toBeFalse();
});

test('an admin with products.approve can approve but a stranger cannot', function () {
    Permission::findOrCreate('products.approve', 'web');

    $admin = User::factory()->admin()->create();
    $admin->givePermissionTo('products.approve');

    $stranger = User::factory()->create();

    expect($admin->can('approve', Product::class))->toBeTrue()
        ->and($stranger->can('approve', Product::class))->toBeFalse();
});

test('an admin with products.feature can feature but a stranger cannot', function () {
    Permission::findOrCreate('products.feature', 'web');

    $admin = User::factory()->admin()->create();
    $admin->givePermissionTo('products.feature');

    $stranger = User::factory()->create();

    expect($admin->can('feature', Product::class))->toBeTrue()
        ->and($stranger->can('feature', Product::class))->toBeFalse();
});
