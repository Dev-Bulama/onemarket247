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
 * Moves a referring agent's share of a vendor order's credit from
 * pending to available once the order reaches Completed — mirrors
 * SettleVendorWalletCreditAction exactly. A no-op when the vendor has no
 * agent, or nothing was ever credited for this order. Called from
 * App\Actions\Order\UpdateVendorOrderStatusAction.
 */
class SettleAgentWalletCreditAction
{
    use LocksAgentWallet, RecordsAgentWalletTransaction;

    public function handle(VendorOrder $vendorOrder): void
    {
        $agent = $vendorOrder->vendor?->agent;

        if (! $agent) {
            return;
        }

        $pendingCredit = AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)
            ->where('type', WalletTransactionType::SaleCreditPending)
            ->sum('amount');

        if ($pendingCredit <= 0) {
            return;
        }

        $alreadySettled = AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)
            ->where('type', WalletTransactionType::SaleCreditAvailable)
            ->exists();

        if ($alreadySettled) {
            return;
        }

        DB::transaction(function () use ($agent, $vendorOrder, $pendingCredit) {
            $wallet = $this->lockedAgentWallet($agent);

            $this->recordAgentTransaction(
                $wallet,
                WalletTransactionType::SaleCreditAvailable,
                WalletBalanceBucket::Pending,
                -$pendingCredit,
                vendorOrder: $vendorOrder,
            );

            $this->recordAgentTransaction(
                $wallet,
                WalletTransactionType::SaleCreditAvailable,
                WalletBalanceBucket::Available,
                $pendingCredit,
                vendorOrder: $vendorOrder,
            );
        });
    }
}
