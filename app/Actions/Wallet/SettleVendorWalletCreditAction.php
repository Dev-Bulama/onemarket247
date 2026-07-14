<?php

namespace App\Actions\Wallet;

use App\Actions\Wallet\Concerns\LocksVendorWallet;
use App\Actions\Wallet\Concerns\RecordsWalletTransaction;
use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Models\VendorOrder;
use App\Models\VendorWalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Moves a vendor order's credit from pending to available once the order
 * reaches Completed — see docs/architecture/09-lifecycles.md "Vendor
 * Wallet Lifecycle" ("moved from pending to available balance after
 * settlement delay / delivery confirmation"; this project uses delivery
 * confirmation — VendorOrderStatus::Completed — rather than a time-based
 * delay). Writes two ledger rows (Pending -X, Available +X) so each
 * bucket's own running sum stays reconcilable independently. Called from
 * App\Actions\Order\UpdateVendorOrderStatusAction.
 */
class SettleVendorWalletCreditAction
{
    use LocksVendorWallet, RecordsWalletTransaction;

    public function handle(VendorOrder $vendorOrder): void
    {
        $net = $vendorOrder->netCommissionAmount();

        if ($net <= 0) {
            return;
        }

        $alreadySettled = VendorWalletTransaction::where('vendor_order_id', $vendorOrder->id)
            ->where('type', WalletTransactionType::SaleCreditAvailable)
            ->exists();

        if ($alreadySettled) {
            return;
        }

        DB::transaction(function () use ($vendorOrder, $net) {
            $wallet = $this->lockedWallet($vendorOrder->vendor);

            $this->recordTransaction(
                $wallet,
                WalletTransactionType::SaleCreditAvailable,
                WalletBalanceBucket::Pending,
                -$net,
                vendorOrder: $vendorOrder,
            );

            $this->recordTransaction(
                $wallet,
                WalletTransactionType::SaleCreditAvailable,
                WalletBalanceBucket::Available,
                $net,
                vendorOrder: $vendorOrder,
            );
        });
    }
}
