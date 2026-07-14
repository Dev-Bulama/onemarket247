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
 * On OrderPaid, the net-of-commission amount is credited to the vendor's
 * pending balance — held there until the order reaches Completed (see
 * SettleVendorWalletCreditAction) — per
 * docs/architecture/09-lifecycles.md "Vendor Wallet Lifecycle". Called
 * from App\Actions\Payment\VerifyPaymentAction on a successful payment.
 */
class CreditVendorWalletAction
{
    use LocksVendorWallet, RecordsWalletTransaction;

    public function handle(VendorOrder $vendorOrder): void
    {
        $net = $vendorOrder->netCommissionAmount();

        if ($net <= 0) {
            return;
        }

        $alreadyCredited = VendorWalletTransaction::where('vendor_order_id', $vendorOrder->id)
            ->where('type', WalletTransactionType::SaleCreditPending)
            ->exists();

        if ($alreadyCredited) {
            return;
        }

        DB::transaction(function () use ($vendorOrder, $net) {
            $wallet = $this->lockedWallet($vendorOrder->vendor);

            $this->recordTransaction(
                $wallet,
                WalletTransactionType::SaleCreditPending,
                WalletBalanceBucket::Pending,
                $net,
                vendorOrder: $vendorOrder,
            );
        });
    }
}
