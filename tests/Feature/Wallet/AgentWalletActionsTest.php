<?php

use App\Actions\Wallet\CreditAgentWalletAction;
use App\Actions\Wallet\ReverseAgentWalletCreditAction;
use App\Actions\Wallet\SettleAgentWalletCreditAction;
use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Models\Agent;
use App\Models\AgentWallet;
use App\Models\AgentWalletTransaction;
use App\Models\OrderItem;
use App\Models\OrderItemCommission;
use App\Models\Vendor;
use App\Models\VendorOrder;

function vendorOrderWithAgentCommission(int $platformCommission, ?Agent $agent = null): VendorOrder
{
    $agent ??= Agent::factory()->create(['commission_rate' => 10]);
    $vendor = Vendor::factory()->create(['agent_id' => $agent->id]);
    $vendorOrder = VendorOrder::factory()->create(['vendor_id' => $vendor->id]);
    $item = OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id, 'line_total' => $platformCommission + 9000]);

    OrderItemCommission::factory()->create([
        'order_item_id' => $item->id,
        'gross_amount' => $platformCommission + 9000,
        'commission_amount' => $platformCommission,
        'net_amount' => 9000,
    ]);

    return $vendorOrder->fresh();
}

test('crediting an agent wallet moves its commission-rate share of the platform commission into pending balance', function () {
    $vendorOrder = vendorOrderWithAgentCommission(1000);

    app(CreditAgentWalletAction::class)->handle($vendorOrder);

    $wallet = AgentWallet::where('agent_id', $vendorOrder->vendor->agent_id)->firstOrFail();
    expect($wallet->pending_balance)->toBe(100)
        ->and($wallet->available_balance)->toBe(0);

    expect(AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)->count())->toBe(1);
});

test('a vendor with no referring agent produces no agent wallet activity', function () {
    $vendor = Vendor::factory()->create(['agent_id' => null]);
    $vendorOrder = VendorOrder::factory()->create(['vendor_id' => $vendor->id]);
    $item = OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id, 'line_total' => 1000]);
    OrderItemCommission::factory()->create(['order_item_id' => $item->id, 'gross_amount' => 1000, 'commission_amount' => 100, 'net_amount' => 900]);

    app(CreditAgentWalletAction::class)->handle($vendorOrder->fresh());

    expect(AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)->count())->toBe(0);
});

test('crediting the same vendor order twice is a no-op the second time', function () {
    $vendorOrder = vendorOrderWithAgentCommission(1000);

    app(CreditAgentWalletAction::class)->handle($vendorOrder);
    app(CreditAgentWalletAction::class)->handle($vendorOrder);

    $wallet = AgentWallet::where('agent_id', $vendorOrder->vendor->agent_id)->firstOrFail();
    expect($wallet->pending_balance)->toBe(100);
    expect(AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)->count())->toBe(1);
});

test('settling an agent credit moves it from pending to available', function () {
    $vendorOrder = vendorOrderWithAgentCommission(1000);
    app(CreditAgentWalletAction::class)->handle($vendorOrder);

    app(SettleAgentWalletCreditAction::class)->handle($vendorOrder);

    $wallet = AgentWallet::where('agent_id', $vendorOrder->vendor->agent_id)->firstOrFail();
    expect($wallet->pending_balance)->toBe(0)
        ->and($wallet->available_balance)->toBe(100);

    expect(AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)->pluck('type'))
        ->toEqual(collect([WalletTransactionType::SaleCreditPending, WalletTransactionType::SaleCreditAvailable, WalletTransactionType::SaleCreditAvailable]));
});

test('settling the same agent credit twice is a no-op the second time', function () {
    $vendorOrder = vendorOrderWithAgentCommission(1000);
    app(CreditAgentWalletAction::class)->handle($vendorOrder);

    app(SettleAgentWalletCreditAction::class)->handle($vendorOrder);
    app(SettleAgentWalletCreditAction::class)->handle($vendorOrder);

    $wallet = AgentWallet::where('agent_id', $vendorOrder->vendor->agent_id)->firstOrFail();
    expect($wallet->available_balance)->toBe(100);
});

test('reversing an agent credit still in pending debits the pending balance', function () {
    $vendorOrder = vendorOrderWithAgentCommission(1000);
    app(CreditAgentWalletAction::class)->handle($vendorOrder);

    app(ReverseAgentWalletCreditAction::class)->handle($vendorOrder, refundedAmount: 10000, orderTotal: 10000);

    $wallet = AgentWallet::where('agent_id', $vendorOrder->vendor->agent_id)->firstOrFail();
    expect($wallet->pending_balance)->toBe(0)
        ->and($wallet->available_balance)->toBe(0);
});

test('reversing an agent credit that has already settled debits the available balance instead', function () {
    $vendorOrder = vendorOrderWithAgentCommission(1000);
    app(CreditAgentWalletAction::class)->handle($vendorOrder);
    app(SettleAgentWalletCreditAction::class)->handle($vendorOrder);

    app(ReverseAgentWalletCreditAction::class)->handle($vendorOrder, refundedAmount: 10000, orderTotal: 10000);

    $wallet = AgentWallet::where('agent_id', $vendorOrder->vendor->agent_id)->firstOrFail();
    expect($wallet->available_balance)->toBe(0)
        ->and($wallet->pending_balance)->toBe(0);

    $lastTransaction = AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)->latest('id')->first();
    expect($lastTransaction->type)->toBe(WalletTransactionType::RefundDebit)
        ->and($lastTransaction->balance_bucket)->toBe(WalletBalanceBucket::Available)
        ->and($lastTransaction->amount)->toBe(-100);
});

test('the full checkout-to-refund lifecycle produces the expected agent ledger sequence and balances', function () {
    $vendorOrder = vendorOrderWithAgentCommission(1000);

    app(CreditAgentWalletAction::class)->handle($vendorOrder);
    app(SettleAgentWalletCreditAction::class)->handle($vendorOrder);
    app(ReverseAgentWalletCreditAction::class)->handle($vendorOrder, refundedAmount: 5000, orderTotal: 10000);

    $wallet = AgentWallet::where('agent_id', $vendorOrder->vendor->agent_id)->firstOrFail();
    expect($wallet->available_balance)->toBe(50)
        ->and($wallet->pending_balance)->toBe(0);

    expect(AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)->pluck('type')->map->value->all())
        ->toEqual(['sale_credit_pending', 'sale_credit_available', 'sale_credit_available', 'refund_debit']);
});
