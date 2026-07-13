<?php

use App\Enums\StoreStaffStatus;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Spatie\Permission\Models\Permission;

test('the owning vendor can view their own vendor order', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => Order::factory(), 'vendor_id' => $vendor->id]);

    expect($vendor->user->can('view', $vendorOrder))->toBeTrue();
});

test('an active store staff member with store.orders.manage can view the vendor order', function () {
    Permission::findOrCreate('store.orders.manage', 'web');

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => Order::factory(), 'vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo('store.orders.manage');
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('view', $vendorOrder))->toBeTrue();
});

test('a staff member without the permission cannot view the vendor order', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => Order::factory(), 'vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create();
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('view', $vendorOrder))->toBeFalse();
});

test('a different vendor cannot view someone elses vendor order', function () {
    $vendorA = Vendor::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => Order::factory(), 'vendor_id' => $vendorA->id]);
    $vendorB = Vendor::factory()->create();

    expect($vendorB->user->can('view', $vendorOrder))->toBeFalse();
});

test('an admin with orders.view can view any vendor order', function () {
    Permission::findOrCreate('orders.view', 'web');

    $vendor = Vendor::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => Order::factory(), 'vendor_id' => $vendor->id]);

    $admin = User::factory()->create();
    $admin->givePermissionTo('orders.view');

    expect($admin->can('view', $vendorOrder))->toBeTrue();
});
