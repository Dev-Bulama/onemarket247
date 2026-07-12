<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\DeductStockAction;
use App\Actions\Inventory\ReleaseStockReservationAction;
use App\Actions\Inventory\ReportDamagedStockAction;
use App\Actions\Inventory\ReserveStockAction;
use App\Actions\Inventory\RestoreStockAction;
use App\Enums\StockMovementBucket;
use App\Enums\StockMovementType;
use App\Enums\StockStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;

test('adjusting stock increases on_hand and the products cached quantity', function () {
    $product = Product::factory()->create(['manage_stock' => true, 'stock_quantity' => 0]);
    $warehouse = Warehouse::factory()->create();

    $stock = app(AdjustStockAction::class)->handle($warehouse, $product, 100, 'initial stock');

    expect($stock->on_hand)->toBe(100)
        ->and($product->fresh()->stock_quantity)->toBe(100)
        ->and($product->fresh()->stock_status)->toBe(StockStatus::InStock);
});

test('a negative adjustment cannot take on_hand below zero', function () {
    $product = Product::factory()->create(['manage_stock' => true]);
    $warehouse = Warehouse::factory()->create();
    app(AdjustStockAction::class)->handle($warehouse, $product, 5, 'seed');

    expect(fn () => app(AdjustStockAction::class)->handle($warehouse, $product, -10, 'shrinkage'))
        ->toThrow(InsufficientStockException::class);
});

test('reserving stock increases reserved without touching on_hand', function () {
    $product = Product::factory()->create(['manage_stock' => true]);
    $warehouse = Warehouse::factory()->create();
    app(AdjustStockAction::class)->handle($warehouse, $product, 20, 'seed');

    $stock = app(ReserveStockAction::class)->handle($warehouse, $product, 5);

    expect($stock->on_hand)->toBe(20)
        ->and($stock->reserved)->toBe(5)
        ->and($product->fresh()->stock_quantity)->toBe(15);
});

test('reserving more than is available throws and does not oversell', function () {
    $product = Product::factory()->create(['manage_stock' => true]);
    $warehouse = Warehouse::factory()->create();
    app(AdjustStockAction::class)->handle($warehouse, $product, 5, 'seed');

    expect(fn () => app(ReserveStockAction::class)->handle($warehouse, $product, 6))
        ->toThrow(InsufficientStockException::class);
});

test('releasing a reservation decrements reserved and restores availability', function () {
    $product = Product::factory()->create(['manage_stock' => true]);
    $warehouse = Warehouse::factory()->create();
    app(AdjustStockAction::class)->handle($warehouse, $product, 20, 'seed');
    app(ReserveStockAction::class)->handle($warehouse, $product, 5);

    $stock = app(ReleaseStockReservationAction::class)->handle($warehouse, $product, 5);

    expect($stock->reserved)->toBe(0)
        ->and($product->fresh()->stock_quantity)->toBe(20);
});

test('deducting converts a reservation into a hard removal from both buckets', function () {
    $product = Product::factory()->create(['manage_stock' => true]);
    $warehouse = Warehouse::factory()->create();
    app(AdjustStockAction::class)->handle($warehouse, $product, 20, 'seed');
    app(ReserveStockAction::class)->handle($warehouse, $product, 5);

    $stock = app(DeductStockAction::class)->handle($warehouse, $product, 5);

    expect($stock->on_hand)->toBe(15)
        ->and($stock->reserved)->toBe(0)
        ->and($product->fresh()->stock_quantity)->toBe(15);
});

test('deducting more than was reserved is rejected', function () {
    $product = Product::factory()->create(['manage_stock' => true]);
    $warehouse = Warehouse::factory()->create();
    app(AdjustStockAction::class)->handle($warehouse, $product, 20, 'seed');
    app(ReserveStockAction::class)->handle($warehouse, $product, 2);

    expect(fn () => app(DeductStockAction::class)->handle($warehouse, $product, 5))
        ->toThrow(InsufficientStockException::class);
});

test('restoring stock after a deduction increases on_hand without touching reserved', function () {
    $product = Product::factory()->create(['manage_stock' => true]);
    $warehouse = Warehouse::factory()->create();
    app(AdjustStockAction::class)->handle($warehouse, $product, 20, 'seed');
    app(ReserveStockAction::class)->handle($warehouse, $product, 5);
    app(DeductStockAction::class)->handle($warehouse, $product, 5);

    $stock = app(RestoreStockAction::class)->handle($warehouse, $product, 5);

    expect($stock->on_hand)->toBe(20)
        ->and($stock->reserved)->toBe(0)
        ->and($product->fresh()->stock_quantity)->toBe(20);
});

test('reporting damaged stock moves quantity from on_hand to damaged', function () {
    $product = Product::factory()->create(['manage_stock' => true]);
    $warehouse = Warehouse::factory()->create();
    app(AdjustStockAction::class)->handle($warehouse, $product, 20, 'seed');

    $stock = app(ReportDamagedStockAction::class)->handle($warehouse, $product, 3, 'water damage');

    expect($stock->on_hand)->toBe(17)
        ->and($stock->damaged)->toBe(3)
        ->and($product->fresh()->stock_quantity)->toBe(17);
});

test('hitting zero available stock downgrades status to out of stock', function () {
    $product = Product::factory()->create(['manage_stock' => true]);
    $warehouse = Warehouse::factory()->create();
    app(AdjustStockAction::class)->handle($warehouse, $product, 5, 'seed');

    app(ReserveStockAction::class)->handle($warehouse, $product, 5);
    app(DeductStockAction::class)->handle($warehouse, $product, 5);

    expect($product->fresh()->stock_status)->toBe(StockStatus::OutOfStock);
});

test('an explicit backorder status is not overwritten while stock stays at zero', function () {
    $product = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::OnBackorder, 'stock_quantity' => 0]);
    $warehouse = Warehouse::factory()->create();

    // A zero-quantity damage report still runs the recalculation without
    // ever pushing available stock above zero, so the vendor's explicit
    // backorder choice is never cleared by a replenishment step.
    app(ReportDamagedStockAction::class)->handle($warehouse, $product, 0, 'stocktake, nothing damaged');

    expect($product->fresh()->stock_status)->toBe(StockStatus::OnBackorder);
});

test('replenishing stock clears an out of stock status back to in stock', function () {
    $product = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::OutOfStock, 'stock_quantity' => 0]);
    $warehouse = Warehouse::factory()->create();

    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'restock');

    expect($product->fresh()->stock_status)->toBe(StockStatus::InStock);
});

test('a product opted out of stock management is never recalculated', function () {
    $product = Product::factory()->create(['manage_stock' => false, 'stock_quantity' => null]);
    $warehouse = Warehouse::factory()->create();

    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    expect($product->fresh()->stock_quantity)->toBeNull();
});

test('every stock mutation writes an immutable movement ledger entry', function () {
    $product = Product::factory()->create(['manage_stock' => true]);
    $warehouse = Warehouse::factory()->create();

    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    $movement = StockMovement::first();
    expect($movement->type)->toBe(StockMovementType::Adjustment)
        ->and($movement->bucket)->toBe(StockMovementBucket::OnHand)
        ->and($movement->quantity_delta)->toBe(10);
});
