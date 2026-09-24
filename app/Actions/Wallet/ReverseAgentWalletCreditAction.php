<?php

namespace App\Actions\Wallet;

use App\Actions\Wallet\Concerns\LocksAgentWallet;
use App\Actions\Wallet\Concerns\RecordsAgentWalletTransaction;
use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Models\AgentWalletTransaction;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\DB;

/**
 * On a refund, reverses the referring agent's proportional share of a
 * vendor order's credit — the same refund-to-order-total ratio
 * App\Actions\Payment\RefundPaymentAction applies to the vendor's own
 * reversal, applied here to the agent's original credit for that order
 * instead of recomputing from the commission rate again. A no-op when
 * the vendor has no agent, or nothing was ever credited for this order.
 * Debits whichever bucket the credit currently sits in — mirrors
 * ReverseVendorWalletCreditAction. Called from
 * App\Actions\Payment\RefundPaymentAction.
 */
class ReverseAgentWalletCreditAction
{
    use LocksAgentWallet, RecordsAgentWalletTransaction;

    public function handle(VendorOrder $vendorOrder, int $refundedAmount, int $orderTotal): void
    {
        $agent = $vendorOrder->vendor?->agent;

        if (! $agent || $refundedAmount <= 0 || $orderTotal <= 0) {
            return;
        }

        $originalCredit = AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)
            ->where('type', WalletTransactionType::SaleCreditPending)
            ->sum('amount');

        if ($originalCredit <= 0) {
            return;
        }

        $amount = (int) round($refundedAmount * $originalCredit / $orderTotal);

        if ($amount <= 0) {
            return;
        }

        $isSettled = AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)
            ->where('type', WalletTransactionType::SaleCreditAvailable)
            ->exists();

        $bucket = $isSettled ? WalletBalanceBucket::Available : WalletBalanceBucket::Pending;

        DB::transaction(function () use ($agent, $vendorOrder, $amount, $bucket) {
            $wallet = $this->lockedAgentWallet($agent);

            $this->recordAgentTransaction(
                $wallet,
                WalletTransactionType::RefundDebit,
                $bucket,
                -$amount,
                vendorOrder: $vendorOrder,
            );
        });
    }
}
