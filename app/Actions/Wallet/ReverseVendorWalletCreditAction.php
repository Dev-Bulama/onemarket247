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
 * On a refund, reverses the proportional share of a vendor order's
 * credit — see docs/architecture/09-lifecycles.md "Commission Lifecycle"
 * point 4 and "Vendor Wallet Lifecycle"'s refund_debit entry. Debits
 * whichever bucket that vendor order's credit currently sits in: still
 * Pending if the order hasn't reached Completed yet, otherwise Available
 * (determined by whether a SaleCreditAvailable settlement entry already
 * exists for this vendor order). Called from
 * App\Actions\Payment\RefundPaymentAction.
 */
class ReverseVendorWalletCreditAction
{
    use LocksVendorWallet, RecordsWalletTransaction;

    public function handle(VendorOrder $vendorOrder, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $isSettled = VendorWalletTransaction::where('vendor_order_id', $vendorOrder->id)
            ->where('type', WalletTransactionType::SaleCreditAvailable)
            ->exists();

        $bucket = $isSettled ? WalletBalanceBucket::Available : WalletBalanceBucket::Pending;

        DB::transaction(function () use ($vendorOrder, $amount, $bucket) {
            $wallet = $this->lockedWallet($vendorOrder->vendor);

            $this->recordTransaction(
                $wallet,
                WalletTransactionType::RefundDebit,
                $bucket,
                -$amount,
                vendorOrder: $vendorOrder,
            );
        });
    }
}
