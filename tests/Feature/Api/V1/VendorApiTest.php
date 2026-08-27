<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorWallet;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WithdrawalMethod;

function vendorToken(): array
{
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    return [$vendor, $token];
}

test('a customer token cannot access the vendor api', function () {
    $customer = User::factory()->create();
    $token = $customer->createToken('t', ['customer:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/store')
        ->assertForbidden();
});

test('the vendor api requires authentication', function () {
    $this->getJson('/api/v1/vendor/store')->assertUnauthorized();
});

test('a vendor can view and update their store', function () {
    [$vendor, $token] = vendorToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/store')
        ->assertOk()
        ->assertJsonPath('data.name', $vendor->store->name);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/v1/vendor/store', [
            'name' => 'Updated Store Name',
            'status' => 'active',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Store Name');
});

test('a vendor can list and update their own products, but not another vendor\'s', function () {
    [$vendor, $token] = vendorToken();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'price' => 1000]);

    $otherVendor = Vendor::factory()->create();
    $otherProduct = Product::factory()->create(['vendor_id' => $otherVendor->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/products')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $product->id);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/vendor/products/{$product->id}", [
            'price' => 2500,
            'manage_stock' => false,
            'stock_status' => 'in_stock',
        ])
        ->assertOk()
        ->assertJsonPath('data.price.amount', 2500);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/vendor/products/{$otherProduct->id}")
        ->assertForbidden();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/vendor/products/{$otherProduct->id}", [
            'price' => 1,
            'manage_stock' => false,
            'stock_status' => 'in_stock',
        ])
        ->assertForbidden();
});

test('a vendor can view and adjust their own inventory', function () {
    [$vendor, $token] = vendorToken();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 5, 'seed');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/inventory')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.on_hand', 5);

    $stock = WarehouseStock::where('warehouse_id', $warehouse->id)->firstOrFail();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/vendor/inventory/{$stock->id}", ['delta' => 10, 'reason' => 'Restock'])
        ->assertOk()
        ->assertJsonPath('data.on_hand', 15);
});

test('a vendor cannot adjust stock belonging to another vendor\'s warehouse', function () {
    [$vendor, $token] = vendorToken();

    $otherVendor = Vendor::factory()->create();
    $otherWarehouse = Warehouse::factory()->create(['vendor_id' => $otherVendor->id]);
    $otherProduct = Product::factory()->create(['vendor_id' => $otherVendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($otherWarehouse, $otherProduct, 5, 'seed');
    $stock = WarehouseStock::where('warehouse_id', $otherWarehouse->id)->firstOrFail();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/vendor/inventory/{$stock->id}", ['delta' => 10, 'reason' => 'Nope'])
        ->assertForbidden();
});

test('a vendor can view orders and progress their status', function () {
    [$vendor, $token] = vendorToken();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'vendor_id' => $vendor->id, 'status' => VendorOrderStatus::Confirmed]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/orders')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/vendor/orders/{$vendorOrder->id}/status", ['status' => 'processing'])
        ->assertOk()
        ->assertJsonPath('data.status', 'processing');
});

test('a vendor can cancel their own order but not one belonging to another vendor', function () {
    [$vendor, $token] = vendorToken();
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'vendor_id' => $vendor->id, 'status' => VendorOrderStatus::Confirmed]);

    $otherVendor = Vendor::factory()->create();
    $otherOrder = Order::factory()->create();
    $otherVendorOrder = VendorOrder::factory()->create(['order_id' => $otherOrder->id, 'vendor_id' => $otherVendor->id, 'status' => VendorOrderStatus::Confirmed]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/vendor/orders/{$otherVendorOrder->id}/cancel", ['reason' => 'Not mine'])
        ->assertForbidden();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/vendor/orders/{$vendorOrder->id}/cancel", ['reason' => 'Out of stock'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

test('a vendor can view earnings and their transaction history', function () {
    [$vendor, $token] = vendorToken();
    $wallet = VendorWallet::factory()->create(['vendor_id' => $vendor->id, 'available_balance' => 50000]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/earnings')
        ->assertOk()
        ->assertJsonPath('data.available_balance.amount', 50000);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/earnings/transactions')
        ->assertOk();
});

test('a vendor can add a withdrawal method, request a withdrawal, and cancel it', function () {
    [$vendor, $token] = vendorToken();
    VendorWallet::factory()->create(['vendor_id' => $vendor->id, 'available_balance' => 100000]);

    $method = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/withdrawals/methods', [
            'bank_name' => 'First Bank',
            'account_name' => 'Vendor Name',
            'account_number' => '1234567890',
        ])->assertCreated();

    $methodId = $method->json('data.id');

    $withdrawal = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/withdrawals', [
            'withdrawal_method_id' => $methodId,
            'amount' => 20000,
        ])->assertCreated();

    $withdrawalId = $withdrawal->json('data.id');
    expect($withdrawal->json('data.status'))->toBe('pending');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/vendor/withdrawals/{$withdrawalId}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

test('requesting a withdrawal larger than the available balance is rejected', function () {
    [$vendor, $token] = vendorToken();
    VendorWallet::factory()->create(['vendor_id' => $vendor->id, 'available_balance' => 1000]);
    $method = WithdrawalMethod::factory()->create(['vendor_id' => $vendor->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/withdrawals', [
            'withdrawal_method_id' => $method->id,
            'amount' => 50000,
        ])
        ->assertStatus(422)
        ->assertJsonPath('error_code', 'INSUFFICIENT_BALANCE');
});
