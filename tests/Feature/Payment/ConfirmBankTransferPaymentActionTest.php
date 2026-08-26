<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\ReserveStockAction;
use App\Actions\Payment\ConfirmBankTransferPaymentAction;
use App\Enums\PaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\Warehouse;
use App\Models\WarehouseStock;

function setUpPendingBankTransferOrder(int $total = 50000): array
{
    $vendor = Vendor::factory()->create();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    $order = Order::factory()->create(['total' => $total]);
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'vendor_id' => $vendor->id, 'status' => VendorOrderStatus::PendingPayment]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id, 'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 3]);
    app(ReserveStockAction::class)->handle($warehouse, $product, 3, null, $vendorOrder);

    $payment = Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => $total,
        'status' => PaymentStatus::Pending,
        'gateway' => 'bank_transfer',
    ]);

    return compact('order', 'vendorOrder', 'payment', 'product', 'warehouse');
}

test('confirming a bank transfer marks the payment paid and confirms the vendor order', function () {
    ['vendorOrder' => $vendorOrder, 'payment' => $payment, 'product' => $product, 'warehouse' => $warehouse] = setUpPendingBankTransferOrder();
    $admin = User::factory()->admin()->create();

    $confirmed = app(ConfirmBankTransferPaymentAction::class)->handle($payment, $admin);

    expect($confirmed->status)->toBe(PaymentStatus::Paid)
        ->and($confirmed->paid_at)->not->toBeNull()
        ->and($confirmed->meta['confirmed_by'])->toBe($admin->id);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Confirmed);

    $stock = WarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail();
    expect($stock->reserved)->toBe(0)->and($stock->on_hand)->toBe(7);
});

test('confirming a bank transfer twice is idempotent', function () {
    ['payment' => $payment] = setUpPendingBankTransferOrder();

    app(ConfirmBankTransferPaymentAction::class)->handle($payment);
    $second = app(ConfirmBankTransferPaymentAction::class)->handle($payment->fresh());

    expect($second->status)->toBe(PaymentStatus::Paid);
});

test('a payment that was not made by bank transfer cannot be confirmed this way', function () {
    ['payment' => $payment] = setUpPendingBankTransferOrder();
    $payment->update(['gateway' => 'paystack']);

    expect(fn () => app(ConfirmBankTransferPaymentAction::class)->handle($payment))
        ->toThrow(PaymentGatewayException::class);
});
