<?php

namespace App\Actions\Wallet\Concerns;

use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\VendorOrder;
use App\Models\VendorWallet;
use App\Models\VendorWalletTransaction;
use App\Models\Withdrawal;

trait RecordsWalletTransaction
{
    private function recordTransaction(
        VendorWallet $wallet,
        WalletTransactionType $type,
        WalletBalanceBucket $bucket,
        int $delta,
        ?VendorOrder $vendorOrder = null,
        ?Withdrawal $withdrawal = null,
        ?string $reason = null,
        ?User $actor = null,
    ): void {
        VendorWalletTransaction::create([
            'vendor_wallet_id' => $wallet->id,
            'vendor_order_id' => $vendorOrder?->id,
            'withdrawal_id' => $withdrawal?->id,
            'type' => $type,
            'balance_bucket' => $bucket,
            'amount' => $delta,
            'reason' => $reason,
            'created_by' => $actor?->id,
        ]);

        $column = $bucket->column();
        $wallet->update([$column => $wallet->{$column} + $delta]);
    }
}
