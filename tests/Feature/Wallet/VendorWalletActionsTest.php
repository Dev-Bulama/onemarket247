<?php

use App\Actions\Wallet\CreditVendorWalletAction;
use App\Actions\Wallet\ReverseVendorWalletCreditAction;
use App\Actions\Wallet\SettleVendorWalletCreditAction;
use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Models\OrderItem;
use App\Models\OrderItemCommission;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorWallet;
use App\Models\VendorWalletTransaction;

function vendorOrderWithNetCommission(int $net, ?Vendor $vendor = null): VendorOrder
{
    $vendor ??= Vendor::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['vendor_id' => $vendor->id]);
    $item = OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id, 'line_total' => $net + 100]);

    OrderItemCommission::factory()->create([
        'order_item_id' => $item->id,
        'gross_amount' => $net + 100,
        'commission_amount' => 100,
        'net_amount' => $net,
    ]);

    return $vendorOrder->fresh();
}

test('crediting a vendor wallet moves the net commission amount into pending balance', function () {
    $vendorOrder = vendorOrderWithNetCommission(9000);

    app(CreditVendorWalletAction::class)->handle($vendorOrder);

    $wallet = VendorWallet::where('vendor_id', $vendorOrder->vendor_id)->firstOrFail();
    expect($wallet->pending_balance)->toBe(9000)
        ->and($wallet->available_balance)->toBe(0);

    expect(VendorWalletTransaction::where('vendor_order_id', $vendorOrder->id)->count())->toBe(1);
});

test('crediting the same vendor order twice is a no-op the second time', function () {
    $vendorOrder = vendorOrderWithNetCommission(9000);

    app(CreditVendorWalletAction::class)->handle($vendorOrder);
    app(CreditVendorWalletAction::class)->handle($vendorOrder);

    $wallet = VendorWallet::where('vendor_id', $vendorOrder->vendor_id)->firstOrFail();
    expect($wallet->pending_balance)->toBe(9000);
    expect(VendorWalletTransaction::where('vendor_order_id', $vendorOrder->id)->count())->toBe(1);
});

test('settling a vendor order moves its credit from pending to available', function () {
    $vendorOrder = vendorOrderWithNetCommission(9000);
    app(CreditVendorWalletAction::class)->handle($vendorOrder);

    app(SettleVendorWalletCreditAction::class)->handle($vendorOrder);

    $wallet = VendorWallet::where('vendor_id', $vendorOrder->vendor_id)->firstOrFail();
    expect($wallet->pending_balance)->toBe(0)
        ->and($wallet->available_balance)->toBe(9000);

    expect(VendorWalletTransaction::where('vendor_order_id', $vendorOrder->id)->pluck('type'))
        ->toEqual(collect([WalletTransactionType::SaleCreditPending, WalletTransactionType::SaleCreditAvailable, WalletTransactionType::SaleCreditAvailable]));
});

test('settling the same vendor order twice is a no-op the second time', function () {
    $vendorOrder = vendorOrderWithNetCommission(9000);
    app(CreditVendorWalletAction::class)->handle($vendorOrder);

    app(SettleVendorWalletCreditAction::class)->handle($vendorOrder);
    app(SettleVendorWalletCreditAction::class)->handle($vendorOrder);

    $wallet = VendorWallet::where('vendor_id', $vendorOrder->vendor_id)->firstOrFail();
    expect($wallet->available_balance)->toBe(9000);
});

test('reversing credit still in pending debits the pending balance', function () {
    $vendorOrder = vendorOrderWithNetCommission(9000);
    app(CreditVendorWalletAction::class)->handle($vendorOrder);

    app(ReverseVendorWalletCreditAction::class)->handle($vendorOrder, 9000);

    $wallet = VendorWallet::where('vendor_id', $vendorOrder->vendor_id)->firstOrFail();
    expect($wallet->pending_balance)->toBe(0)
        ->and($wallet->available_balance)->toBe(0);
});

test('reversing credit that has already settled debits the available balance instead', function () {
    $vendorOrder = vendorOrderWithNetCommission(9000);
    app(CreditVendorWalletAction::class)->handle($vendorOrder);
    app(SettleVendorWalletCreditAction::class)->handle($vendorOrder);

    app(ReverseVendorWalletCreditAction::class)->handle($vendorOrder, 9000);

    $wallet = VendorWallet::where('vendor_id', $vendorOrder->vendor_id)->firstOrFail();
    expect($wallet->available_balance)->toBe(0)
        ->and($wallet->pending_balance)->toBe(0);

    $lastTransaction = VendorWalletTransaction::where('vendor_order_id', $vendorOrder->id)->latest('id')->first();
    expect($lastTransaction->type)->toBe(WalletTransactionType::RefundDebit)
        ->and($lastTransaction->balance_bucket)->toBe(WalletBalanceBucket::Available)
        ->and($lastTransaction->amount)->toBe(-9000);
});

test('the full checkout-to-refund lifecycle produces the expected ledger sequence and balances', function () {
    $vendorOrder = vendorOrderWithNetCommission(9000);

    app(CreditVendorWalletAction::class)->handle($vendorOrder);
    app(SettleVendorWalletCreditAction::class)->handle($vendorOrder);
    app(ReverseVendorWalletCreditAction::class)->handle($vendorOrder, 4500);

    $wallet = VendorWallet::where('vendor_id', $vendorOrder->vendor_id)->firstOrFail();
    expect($wallet->available_balance)->toBe(4500)
        ->and($wallet->pending_balance)->toBe(0);

    expect(VendorWalletTransaction::where('vendor_order_id', $vendorOrder->id)->pluck('type')->map->value->all())
        ->toEqual(['sale_credit_pending', 'sale_credit_available', 'sale_credit_available', 'refund_debit']);
});
