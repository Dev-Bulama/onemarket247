<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\CancelStockTransferAction;
use App\Actions\Inventory\CompleteStockTransferAction;
use App\Actions\Inventory\DispatchStockTransferAction;
use App\Actions\Inventory\RequestStockTransferAction;
use App\Enums\StockTransferStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Warehouse;

test('requesting a transfer creates a pending record with no stock movement yet', function () {
    $vendor = Vendor::factory()->create();
    $from = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $to = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($from, $product, 50, 'seed');

    $transfer = app(RequestStockTransferAction::class)->handle($from, $to, [
        ['sellable' => $product, 'quantity' => 10],
    ]);

    expect($transfer->status)->toBe(StockTransferStatus::Pending)
        ->and($transfer->items)->toHaveCount(1)
        ->and($product->fresh()->stock_quantity)->toBe(50);
});

test('a transfer cannot be requested between warehouses of different vendors', function () {
    $from = Warehouse::factory()->create();
    $to = Warehouse::factory()->create();
    $product = Product::factory()->for($from->vendor)->create();

    expect(fn () => app(RequestStockTransferAction::class)->handle($from, $to, [
        ['sellable' => $product, 'quantity' => 1],
    ]))->toThrow(InvalidArgumentException::class);
});

test('a transfer cannot be requested to the same warehouse', function () {
    $warehouse = Warehouse::factory()->create();
    $product = Product::factory()->for($warehouse->vendor)->create();

    expect(fn () => app(RequestStockTransferAction::class)->handle($warehouse, $warehouse, [
        ['sellable' => $product, 'quantity' => 1],
    ]))->toThrow(InvalidArgumentException::class);
});

test('dispatching moves quantity from source on_hand to destination incoming', function () {
    $vendor = Vendor::factory()->create();
    $from = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $to = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($from, $product, 50, 'seed');

    $transfer = app(RequestStockTransferAction::class)->handle($from, $to, [
        ['sellable' => $product, 'quantity' => 20],
    ]);
    $transfer = app(DispatchStockTransferAction::class)->handle($transfer);

    $fromStock = $from->stocks()->where('product_id', $product->id)->firstOrFail();
    $toStock = $to->stocks()->where('product_id', $product->id)->firstOrFail();

    expect($transfer->status)->toBe(StockTransferStatus::InTransit)
        ->and($fromStock->on_hand)->toBe(30)
        ->and($toStock->incoming)->toBe(20)
        ->and($toStock->on_hand)->toBe(0)
        ->and($product->fresh()->stock_quantity)->toBe(30);
});

test('dispatching more than is on hand at the source is rejected', function () {
    $vendor = Vendor::factory()->create();
    $from = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $to = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($from, $product, 5, 'seed');

    $transfer = app(RequestStockTransferAction::class)->handle($from, $to, [
        ['sellable' => $product, 'quantity' => 5],
    ]);
    // Someone deducts stock at the source after the request but before dispatch.
    $from->stocks()->where('product_id', $product->id)->update(['on_hand' => 2]);

    expect(fn () => app(DispatchStockTransferAction::class)->handle($transfer))
        ->toThrow(InsufficientStockException::class);
});

test('completing a transfer moves quantity from incoming to on_hand at the destination', function () {
    $vendor = Vendor::factory()->create();
    $from = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $to = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($from, $product, 50, 'seed');

    $transfer = app(RequestStockTransferAction::class)->handle($from, $to, [
        ['sellable' => $product, 'quantity' => 20],
    ]);
    $transfer = app(DispatchStockTransferAction::class)->handle($transfer);
    $transfer = app(CompleteStockTransferAction::class)->handle($transfer);

    $toStock = $to->stocks()->where('product_id', $product->id)->firstOrFail();

    expect($transfer->status)->toBe(StockTransferStatus::Completed)
        ->and($transfer->completed_at)->not->toBeNull()
        ->and($toStock->incoming)->toBe(0)
        ->and($toStock->on_hand)->toBe(20)
        ->and($product->fresh()->stock_quantity)->toBe(50);
});

test('a pending transfer can be cancelled with no stock effect', function () {
    $vendor = Vendor::factory()->create();
    $from = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $to = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($from, $product, 50, 'seed');

    $transfer = app(RequestStockTransferAction::class)->handle($from, $to, [
        ['sellable' => $product, 'quantity' => 20],
    ]);
    $transfer = app(CancelStockTransferAction::class)->handle($transfer);

    expect($transfer->status)->toBe(StockTransferStatus::Cancelled)
        ->and($product->fresh()->stock_quantity)->toBe(50);
});

test('cancelling an in-transit transfer reverses the dispatch', function () {
    $vendor = Vendor::factory()->create();
    $from = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $to = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($from, $product, 50, 'seed');

    $transfer = app(RequestStockTransferAction::class)->handle($from, $to, [
        ['sellable' => $product, 'quantity' => 20],
    ]);
    $transfer = app(DispatchStockTransferAction::class)->handle($transfer);
    $transfer = app(CancelStockTransferAction::class)->handle($transfer);

    $fromStock = $from->stocks()->where('product_id', $product->id)->firstOrFail();
    $toStock = $to->stocks()->where('product_id', $product->id)->firstOrFail();

    expect($transfer->status)->toBe(StockTransferStatus::Cancelled)
        ->and($fromStock->on_hand)->toBe(50)
        ->and($toStock->incoming)->toBe(0)
        ->and($product->fresh()->stock_quantity)->toBe(50);
});

test('a completed transfer cannot be cancelled or dispatched again', function () {
    $vendor = Vendor::factory()->create();
    $from = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $to = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($from, $product, 50, 'seed');

    $transfer = app(RequestStockTransferAction::class)->handle($from, $to, [
        ['sellable' => $product, 'quantity' => 20],
    ]);
    $transfer = app(DispatchStockTransferAction::class)->handle($transfer);
    $transfer = app(CompleteStockTransferAction::class)->handle($transfer);

    expect(fn () => app(CancelStockTransferAction::class)->handle($transfer))
        ->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(DispatchStockTransferAction::class)->handle($transfer))
        ->toThrow(InvalidArgumentException::class);
});
