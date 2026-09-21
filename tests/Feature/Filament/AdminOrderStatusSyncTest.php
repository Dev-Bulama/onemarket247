<?php

use App\Enums\VendorOrderStatus;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\VendorOrdersRelationManager;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function orderStatusAdmin(): User
{
    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $admin;
}

test('an admin can advance a vendor order status directly from the platform order page', function () {
    $admin = orderStatusAdmin();
    $vendor = Vendor::factory()->create();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'status' => VendorOrderStatus::PendingPayment,
    ]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id]);

    Livewire::actingAs($admin, 'admin')
        ->test(VendorOrdersRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
        ->callTableAction('transition-confirmed', $vendorOrder, data: ['note' => 'Confirmed by admin']);

    $vendorOrder->refresh();
    expect($vendorOrder->status)->toBe(VendorOrderStatus::Confirmed);

    expect($vendorOrder->statusHistories()->where('status', 'confirmed')->exists())->toBeTrue();
});

test('a status change made from the order page is the same VendorOrder row the vendor dashboard reads', function () {
    $admin = orderStatusAdmin();
    $vendor = Vendor::factory()->create();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'status' => VendorOrderStatus::PendingPayment,
    ]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id]);

    Livewire::actingAs($admin, 'admin')
        ->test(VendorOrdersRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
        ->callTableAction('transition-confirmed', $vendorOrder);

    $this->actingAs($vendor->user, 'vendor')
        ->get("/vendor/vendor-orders/{$vendorOrder->id}")
        ->assertOk()
        ->assertSee('Confirmed');
});

test('an invalid transition is rejected and the vendor order is untouched', function () {
    $admin = orderStatusAdmin();
    $vendor = Vendor::factory()->create();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'status' => VendorOrderStatus::PendingPayment,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(VendorOrdersRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
        ->assertTableActionHidden('transition-completed', $vendorOrder);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::PendingPayment);
});

test('an admin can cancel a vendor order directly from the platform order page', function () {
    $admin = orderStatusAdmin();
    $vendor = Vendor::factory()->create();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'status' => VendorOrderStatus::Confirmed,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(VendorOrdersRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
        ->callTableAction('cancel', $vendorOrder, data: ['reason' => 'Customer requested cancellation.']);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Cancelled);
});
