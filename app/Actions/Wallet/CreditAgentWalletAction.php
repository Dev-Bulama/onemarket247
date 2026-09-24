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
 * When a vendor order that was referred by an agent (Vendor::agent_id) is
 * paid, the agent earns a share of the platform's own commission on that
 * order — never an extra charge to the vendor or a cut of the vendor's
 * own take-home. A no-op when the vendor has no agent. Mirrors
 * CreditVendorWalletAction's pending-then-settle shape; called from the
 * same place, App\Actions\Payment\MarkPaymentPaidAction.
 */
class CreditAgentWalletAction
{
    use LocksAgentWallet, RecordsAgentWalletTransaction;

    public function handle(VendorOrder $vendorOrder): void
    {
        $agent = $vendorOrder->vendor?->agent;

        if (! $agent) {
            return;
        }

        $share = (int) round($vendorOrder->platformCommissionAmount() * (float) $agent->commission_rate / 100);

        if ($share <= 0) {
            return;
        }

        $alreadyCredited = AgentWalletTransaction::where('vendor_order_id', $vendorOrder->id)
            ->where('type', WalletTransactionType::SaleCreditPending)
            ->exists();

        if ($alreadyCredited) {
            return;
        }

        DB::transaction(function () use ($agent, $vendorOrder, $share) {
            $wallet = $this->lockedAgentWallet($agent);

            $this->recordAgentTransaction(
                $wallet,
                WalletTransactionType::SaleCreditPending,
                WalletBalanceBucket::Pending,
                $share,
                vendorOrder: $vendorOrder,
            );
        });
    }
}
