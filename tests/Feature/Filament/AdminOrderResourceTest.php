<?php

use App\Enums\VendorOrderStatus;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\NotesRelationManager;
use App\Filament\Resources\VendorOrders\Pages\ViewVendorOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorOrder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function superAdminForOrders(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('a super admin can load the order and vendor order index and view pages', function () {
    $admin = superAdminForOrders();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id]);

    $this->actingAs($admin, 'admin')->get('/admin/orders')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/orders/{$order->public_id}")->assertOk();
    $this->actingAs($admin, 'admin')->get('/admin/vendor-orders')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/vendor-orders/{$vendorOrder->id}")->assertOk();
});

test('a staff admin without orders.view cannot access orders', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/orders')->assertForbidden();
    $this->actingAs($staff, 'admin')->get('/admin/vendor-orders')->assertForbidden();
});

test('an admin can add an internal note to an order', function () {
    $admin = superAdminForOrders();
    $order = Order::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(NotesRelationManager::class, [
            'ownerRecord' => $order,
            'pageClass' => ViewOrder::class,
        ])
        ->callTableAction('create', data: [
            'visibility' => 'internal',
            'body' => 'Customer called about delayed shipping.',
        ]);

    $note = $order->notes()->firstOrFail();
    expect($note->body)->toBe('Customer called about delayed shipping.')
        ->and($note->author_id)->toBe($admin->id);
});

test('an admin can advance a vendor order to its next status', function () {
    $admin = superAdminForOrders();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'status' => VendorOrderStatus::PendingPayment,
    ]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id]);

    Livewire::actingAs($admin, 'admin')
        ->test(ViewVendorOrder::class, ['record' => $vendorOrder->getRouteKey()])
        ->callAction('transition-confirmed');

    // OrderStatusAggregator groups Confirmed under its IN_PROGRESS set, so
    // the sole child moving to Confirmed rolls the parent up to Processing,
    // not Confirmed — see App\Services\Order\OrderStatusAggregator.
    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Confirmed)
        ->and($order->fresh()->status->value)->toBe('processing');
});

test('an admin can attach a note while advancing a vendor order, and it aggregates onto the parent order', function () {
    $admin = superAdminForOrders();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'status' => VendorOrderStatus::Confirmed,
    ]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id]);

    Livewire::actingAs($admin, 'admin')
        ->test(ViewVendorOrder::class, ['record' => $vendorOrder->getRouteKey()])
        ->callAction('transition-processing', data: ['note' => 'Packing now.']);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Processing);

    $history = $vendorOrder->fresh()->statusHistories()->latest('id')->first();
    expect($history->note)->toBe('Packing now.')
        ->and($history->changed_by)->toBe($admin->id);

    expect($order->fresh()->status->value)->toBe('processing');
});

test('an admin can cancel a vendor order that is still eligible', function () {
    $admin = superAdminForOrders();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'status' => VendorOrderStatus::Confirmed,
    ]);

    Livewire::actingAs($admin, 'admin')
        ->test(ViewVendorOrder::class, ['record' => $vendorOrder->getRouteKey()])
        ->callAction('cancel', data: ['reason' => 'Vendor is out of stock.']);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Cancelled);
});
