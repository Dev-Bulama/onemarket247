<?php

use App\Actions\Inventory\RequestStockTransferAction;
use App\Enums\StoreStaffStatus;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Spatie\Permission\Models\Permission;

function makeTransfer(Vendor $vendor): StockTransfer
{
    $from = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $to = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    return app(RequestStockTransferAction::class)->handle($from, $to, [
        ['sellable' => $product, 'quantity' => 1],
    ]);
}

test('the owning vendor can view and update their own transfer', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $transfer = makeTransfer($vendor);

    expect($vendor->user->can('view', $transfer))->toBeTrue()
        ->and($vendor->user->can('update', $transfer))->toBeTrue()
        ->and($vendor->user->can('create', StockTransfer::class))->toBeTrue();
});

test('an active store staff member with store.inventory.manage can update the transfer', function () {
    Permission::findOrCreate('store.inventory.manage', 'web');

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $transfer = makeTransfer($vendor);

    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo('store.inventory.manage');
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('update', $transfer))->toBeTrue();
});

test('an unrelated vendor cannot view or update another vendors transfer', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $transfer = makeTransfer($vendor);

    $otherVendor = Vendor::factory()->create();

    expect($otherVendor->user->can('view', $transfer))->toBeFalse()
        ->and($otherVendor->user->can('update', $transfer))->toBeFalse();
});

test('an admin with inventory.manage can view any transfer but a stranger cannot', function () {
    Permission::findOrCreate('inventory.manage', 'web');

    $admin = User::factory()->admin()->create();
    $admin->givePermissionTo('inventory.manage');

    $stranger = User::factory()->create();
    $vendor = Vendor::factory()->create();
    $transfer = makeTransfer($vendor);

    expect($admin->can('view', $transfer))->toBeTrue()
        ->and($stranger->can('view', $transfer))->toBeFalse();
});
