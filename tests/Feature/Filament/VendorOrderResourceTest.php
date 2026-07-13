<?php

use App\Enums\UserType;
use App\Enums\VendorOrderStatus;
use App\Filament\Vendor\Resources\VendorOrders\Pages\ViewVendorOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    Filament::setCurrentPanel('vendor');
});

test('a vendor owner can load their order index and view pages', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $vendorOrder = VendorOrder::factory()->create(['vendor_id' => $vendor->id]);

    $this->actingAs($vendor->user, 'vendor')->get('/vendor/vendor-orders')->assertOk();
    $this->actingAs($vendor->user, 'vendor')->get("/vendor/vendor-orders/{$vendorOrder->id}")->assertOk();
});

test('a vendor cannot open another vendors order', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $otherVendor = Vendor::factory()->create();
    Store::factory()->for($otherVendor)->create();
    $otherVendorOrder = VendorOrder::factory()->create(['vendor_id' => $otherVendor->id]);

    $this->actingAs($vendor->user, 'vendor')
        ->get("/vendor/vendor-orders/{$otherVendorOrder->id}")->assertNotFound();
});

test('a vendor owner can advance and cancel their own order', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'status' => VendorOrderStatus::PendingPayment,
    ]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id]);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(ViewVendorOrder::class, ['record' => $vendorOrder->getRouteKey()])
        ->callAction('transition-confirmed');

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Confirmed);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(ViewVendorOrder::class, ['record' => $vendorOrder->getRouteKey()])
        ->callAction('cancel', data: ['reason' => 'Out of stock.']);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Cancelled);
});

test('an active store staff member with store.orders.fulfil can advance a vendor order', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $vendorOrder = VendorOrder::factory()->create([
        'vendor_id' => $vendor->id,
        'status' => VendorOrderStatus::PendingPayment,
    ]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id]);

    $staff = User::factory()->create(['user_type' => UserType::VendorStaff]);
    $staff->assignRole(Role::where('name', 'Vendor Staff - Orders')->where('guard_name', 'vendor')->first());
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staff->id,
    ]);

    Livewire::actingAs($staff, 'vendor')
        ->test(ViewVendorOrder::class, ['record' => $vendorOrder->getRouteKey()])
        ->callAction('transition-confirmed');

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Confirmed);
});

test('a store staff member without store.orders.fulfil cannot advance a vendor order', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $vendorOrder = VendorOrder::factory()->create([
        'vendor_id' => $vendor->id,
        'status' => VendorOrderStatus::PendingPayment,
    ]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id]);

    $staff = User::factory()->create(['user_type' => UserType::VendorStaff]);
    $staff->givePermissionTo(Permission::findOrCreate('store.orders.manage', 'vendor'));
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staff->id,
    ]);

    Livewire::actingAs($staff, 'vendor')
        ->test(ViewVendorOrder::class, ['record' => $vendorOrder->getRouteKey()])
        ->assertActionDoesNotExist('transition-confirmed')
        ->assertActionHidden('cancel');
});
