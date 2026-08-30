<?php

use App\Enums\StoreStaffStatus;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Spatie\Permission\Models\Permission;

test('the owning vendor can update and delete their own warehouse', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);

    expect($vendor->user->can('update', $warehouse))->toBeTrue()
        ->and($vendor->user->can('delete', $warehouse))->toBeTrue()
        ->and($vendor->user->can('create', Warehouse::class))->toBeTrue();
});

test('an active store staff member with store.inventory.manage can update the warehouse', function () {
    // store.* permissions are seeded under the "vendor" guard in
    // production (RolePermissionSeeder) — see WarehousePolicy for why
    // policies now check the permission against that guard explicitly.
    $permission = Permission::findOrCreate('store.inventory.manage', 'vendor');

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo($permission);
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('update', $warehouse))->toBeTrue();
});

test('a store staff member without the permission cannot update the warehouse', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create();
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('update', $warehouse))->toBeFalse();
});

test('an unrelated vendor cannot update or delete another vendors warehouse', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);

    $otherVendor = Vendor::factory()->create();

    expect($otherVendor->user->can('update', $warehouse))->toBeFalse()
        ->and($otherVendor->user->can('delete', $warehouse))->toBeFalse();
});

test('checking access to a warehouse whose vendor has been deleted does not crash', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $vendor->delete();

    expect($warehouse->fresh()->vendor)->toBeNull()
        ->and($vendor->user->can('update', $warehouse->fresh()))->toBeFalse();
});

test('an admin with warehouses.manage can manage any warehouse but a stranger cannot', function () {
    Permission::findOrCreate('warehouses.manage', 'web');

    $admin = User::factory()->admin()->create();
    $admin->givePermissionTo('warehouses.manage');

    $stranger = User::factory()->create();
    $warehouse = Warehouse::factory()->create();

    expect($admin->can('update', $warehouse))->toBeTrue()
        ->and($admin->can('create', Warehouse::class))->toBeTrue()
        ->and($stranger->can('update', $warehouse))->toBeFalse();
});
