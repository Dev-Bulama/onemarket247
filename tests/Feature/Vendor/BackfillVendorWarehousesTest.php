<?php

use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Artisan;

test('it creates a default warehouse and backfills stock for a vendor that has none', function () {
    $vendor = Vendor::factory()->create();
    $stocked = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'manage_stock' => true,
        'stock_quantity' => 40,
        'stock_status' => StockStatus::InStock,
    ]);
    $outOfStock = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'manage_stock' => true,
        'stock_quantity' => 0,
        'stock_status' => StockStatus::OutOfStock,
    ]);

    expect($vendor->warehouses()->count())->toBe(0);

    Artisan::call('vendors:backfill-warehouses');

    $vendor->refresh();
    expect($vendor->warehouses()->count())->toBe(1);

    $warehouse = $vendor->warehouses()->first();
    expect($warehouse->is_default)->toBeTrue();

    $stock = $stocked->warehouseStocks()->first();
    expect($stock)->not->toBeNull()
        ->and($stock->on_hand)->toBe(40)
        ->and($stock->warehouse_id)->toBe($warehouse->id);

    expect($outOfStock->warehouseStocks()->count())->toBe(0);
});

test('it never touches a vendor that already has a warehouse', function () {
    $vendor = Vendor::factory()->create();
    $existing = Warehouse::create([
        'vendor_id' => $vendor->id,
        'name' => 'Existing Warehouse',
        'code' => 'EXIST',
        'is_default' => true,
        'is_active' => true,
    ]);

    Artisan::call('vendors:backfill-warehouses');

    expect($vendor->warehouses()->count())->toBe(1)
        ->and($vendor->warehouses()->first()->id)->toBe($existing->id);
});

test('running it twice does not duplicate stock for an already-backfilled product', function () {
    $vendor = Vendor::factory()->create();
    $product = Product::factory()->create([
        'vendor_id' => $vendor->id,
        'manage_stock' => true,
        'stock_quantity' => 40,
        'stock_status' => StockStatus::InStock,
    ]);

    Artisan::call('vendors:backfill-warehouses');
    Artisan::call('vendors:backfill-warehouses');

    expect($vendor->warehouses()->count())->toBe(1)
        ->and($product->warehouseStocks()->count())->toBe(1);
});
