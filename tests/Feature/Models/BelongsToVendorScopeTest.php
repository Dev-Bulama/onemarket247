<?php

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use App\Models\VendorOrder;
use App\Models\Warehouse;

test('a vendor owner only sees their own store when querying', function () {
    $vendorA = Vendor::factory()->create();
    Store::factory()->for($vendorA)->create();

    $vendorB = Vendor::factory()->create();
    Store::factory()->for($vendorB)->create();

    test()->actingAs($vendorA->user, 'vendor');

    expect(Store::all())->toHaveCount(1)
        ->and(Store::first()->vendor_id)->toBe($vendorA->id);
});

test('the scope is inert outside the vendor guard', function () {
    $vendorA = Vendor::factory()->create();
    Store::factory()->for($vendorA)->create();

    $vendorB = Vendor::factory()->create();
    Store::factory()->for($vendorB)->create();

    expect(Store::all())->toHaveCount(2);
});

test('vendor staff see their own store through their owning vendor, not their own user_id', function () {
    $owner = Vendor::factory()->create();
    Store::factory()->for($owner)->create();

    $otherVendor = Vendor::factory()->create();
    Store::factory()->for($otherVendor)->create();

    $staffUser = User::factory()->create(['user_type' => UserType::VendorStaff]);
    StoreStaff::factory()->create([
        'store_id' => $owner->store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    test()->actingAs($staffUser, 'vendor');

    expect(Store::all())->toHaveCount(1)
        ->and(Store::first()->id)->toBe($owner->store->id);
});

test('vendor documents are isolated the same way', function () {
    $vendorA = Vendor::factory()->create();
    VendorDocument::factory()->create(['vendor_id' => $vendorA->id]);

    $vendorB = Vendor::factory()->create();
    VendorDocument::factory()->create(['vendor_id' => $vendorB->id]);

    test()->actingAs($vendorA->user, 'vendor');

    expect(VendorDocument::all())->toHaveCount(1)
        ->and(VendorDocument::first()->vendor_id)->toBe($vendorA->id);
});

test('products are isolated the same way', function () {
    $vendorA = Vendor::factory()->create();
    Product::factory()->for($vendorA)->create();

    $vendorB = Vendor::factory()->create();
    Product::factory()->for($vendorB)->create();

    test()->actingAs($vendorA->user, 'vendor');

    expect(Product::all())->toHaveCount(1)
        ->and(Product::first()->vendor_id)->toBe($vendorA->id);
});

test('warehouses are isolated the same way', function () {
    $vendorA = Vendor::factory()->create();
    Warehouse::factory()->create(['vendor_id' => $vendorA->id]);

    $vendorB = Vendor::factory()->create();
    Warehouse::factory()->create(['vendor_id' => $vendorB->id]);

    test()->actingAs($vendorA->user, 'vendor');

    expect(Warehouse::all())->toHaveCount(1)
        ->and(Warehouse::first()->vendor_id)->toBe($vendorA->id);
});

test('vendor orders are isolated the same way', function () {
    $vendorA = Vendor::factory()->create();
    VendorOrder::factory()->create(['order_id' => Order::factory(), 'vendor_id' => $vendorA->id]);

    $vendorB = Vendor::factory()->create();
    VendorOrder::factory()->create(['order_id' => Order::factory(), 'vendor_id' => $vendorB->id]);

    test()->actingAs($vendorA->user, 'vendor');

    expect(VendorOrder::all())->toHaveCount(1)
        ->and(VendorOrder::first()->vendor_id)->toBe($vendorA->id);
});
