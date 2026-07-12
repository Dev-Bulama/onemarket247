<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Filament\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Resources\Warehouses\RelationManagers\OutgoingTransfersRelationManager;
use App\Filament\Resources\Warehouses\RelationManagers\StocksRelationManager;
use App\Filament\Widgets\LowStockWidget;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function superAdminForInventory(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('a super admin can load warehouse and stock transfer pages', function () {
    $admin = superAdminForInventory();
    $warehouse = Warehouse::factory()->create();
    $transfer = StockTransfer::factory()->create();

    $this->actingAs($admin, 'admin')->get('/admin/warehouses')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/warehouses/{$warehouse->id}/edit")->assertOk();
    $this->actingAs($admin, 'admin')->get('/admin/stock-transfers')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/stock-transfers/{$transfer->id}")->assertOk();
});

test('a staff admin without warehouses.manage cannot access warehouses', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/warehouses')->assertForbidden();
});

test('admin can add stock to a product through the warehouse stocks relation manager', function () {
    $admin = superAdminForInventory();
    $vendor = Vendor::factory()->create();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);

    Livewire::actingAs($admin, 'admin')
        ->test(StocksRelationManager::class, [
            'ownerRecord' => $warehouse,
            'pageClass' => EditWarehouse::class,
        ])
        ->callTableAction('addStock', data: [
            'product_id' => $product->id,
            'quantity' => 25,
            'reason' => 'Initial stock intake',
        ]);

    expect($product->fresh()->stock_quantity)->toBe(25);
});

test('a full transfer request-dispatch-complete cycle works through the relation manager', function () {
    $admin = superAdminForInventory();
    $vendor = Vendor::factory()->create();
    $fromWarehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $toWarehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);

    app(AdjustStockAction::class)->handle($fromWarehouse, $product, 50, 'seed');

    Livewire::actingAs($admin, 'admin')
        ->test(OutgoingTransfersRelationManager::class, [
            'ownerRecord' => $fromWarehouse,
            'pageClass' => EditWarehouse::class,
        ])
        ->callTableAction('requestTransfer', data: [
            'to_warehouse_id' => $toWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

    $transfer = StockTransfer::firstOrFail();
    expect($transfer->status->value)->toBe('pending');

    $component = Livewire::actingAs($admin, 'admin')
        ->test(OutgoingTransfersRelationManager::class, [
            'ownerRecord' => $fromWarehouse,
            'pageClass' => EditWarehouse::class,
        ]);

    $component->callTableAction('dispatch', $transfer);
    expect($transfer->fresh()->status->value)->toBe('in_transit')
        ->and($product->fresh()->stock_quantity)->toBe(40);

    $component->callTableAction('complete', $transfer);
    expect($transfer->fresh()->status->value)->toBe('completed')
        ->and($product->fresh()->stock_quantity)->toBe(50);
});

test('the low stock widget only lists products at or below their threshold', function () {
    $admin = superAdminForInventory();
    $low = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 2, 'low_stock_threshold' => 5]);
    $healthy = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 50, 'low_stock_threshold' => 5]);
    $untracked = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 0, 'low_stock_threshold' => null]);

    Livewire::actingAs($admin, 'admin')
        ->test(LowStockWidget::class)
        ->assertCanSeeTableRecords([$low])
        ->assertCanNotSeeTableRecords([$healthy, $untracked]);
});
