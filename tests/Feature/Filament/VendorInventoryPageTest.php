<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Filament\Vendor\Pages\Inventory;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

test('a vendor can load the inventory page', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $this->actingAs($vendor->user, 'vendor')->get('/vendor/inventory')->assertOk();
});

test('a vendor can add stock and adjust it through the inventory page', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);

    Filament::setCurrentPanel('vendor');

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Inventory::class)
        ->callTableAction('addStock', data: [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 30,
            'reason' => 'Initial stock',
        ]);

    expect($product->fresh()->stock_quantity)->toBe(30);

    $stock = $product->warehouseStocks()->firstOrFail();

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Inventory::class)
        ->callTableAction('adjust', $stock, data: [
            'delta' => -5,
            'reason' => 'Recount correction',
        ]);

    expect($product->fresh()->stock_quantity)->toBe(25);
});

test('a vendor can request a transfer between their own warehouses through the inventory page', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $warehouse1 = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $warehouse2 = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($warehouse1, $product, 30, 'seed');
    $stock = $product->warehouseStocks()->firstOrFail();

    Filament::setCurrentPanel('vendor');

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Inventory::class)
        ->callTableAction('requestTransfer', $stock, data: [
            'to_warehouse_id' => $warehouse2->id,
            'quantity' => 5,
        ]);

    expect(StockTransfer::count())->toBe(1)
        ->and(StockTransfer::first()->status->value)->toBe('pending');
});

test('a vendor cannot see another vendors warehouse stock on the inventory page', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $otherVendor = Vendor::factory()->create();
    $otherWarehouse = Warehouse::factory()->create(['vendor_id' => $otherVendor->id]);
    $otherProduct = Product::factory()->create(['vendor_id' => $otherVendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($otherWarehouse, $otherProduct, 10, 'seed');

    Filament::setCurrentPanel('vendor');

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Inventory::class)
        ->assertCanNotSeeTableRecords([WarehouseStock::first()]);
});
