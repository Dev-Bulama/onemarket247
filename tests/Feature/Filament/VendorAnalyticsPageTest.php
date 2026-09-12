<?php

use App\Enums\UserType;
use App\Enums\VendorOrderStatus;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

test('a vendor owner can load their own analytics page', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    VendorOrder::factory()->create(['vendor_id' => $vendor->id, 'status' => VendorOrderStatus::Delivered]);

    $this->actingAs($vendor->user, 'vendor')->get('/vendor/analytics')->assertOk();
});

test('a store staff member with store.reports.view can load the analytics page', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();

    $staff = User::factory()->create(['user_type' => UserType::VendorStaff]);
    $staff->givePermissionTo(Permission::findOrCreate('store.reports.view', 'vendor'));
    StoreStaff::factory()->create(['store_id' => $store->id, 'user_id' => $staff->id]);

    $this->actingAs($staff, 'vendor')->get('/vendor/analytics')->assertOk();
});

test('a store staff member without store.reports.view cannot access the analytics page', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();

    $staff = User::factory()->create(['user_type' => UserType::VendorStaff]);
    $staff->assignRole(Role::where('name', 'Vendor Staff - Products')->where('guard_name', 'vendor')->first());
    StoreStaff::factory()->create(['store_id' => $store->id, 'user_id' => $staff->id]);

    $this->actingAs($staff, 'vendor')->get('/vendor/analytics')->assertForbidden();
});
