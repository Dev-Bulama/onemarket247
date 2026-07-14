<?php

namespace App\Actions\Withdrawal;

use App\Actions\Wallet\Concerns\LocksVendorWallet;
use App\Actions\Wallet\Concerns\RecordsWalletTransaction;
use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InvalidWithdrawalTransitionException;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

/**
 * Vendor-initiated cancellation, only while a request is still Pending
 * (see docs/architecture/09-lifecycles.md "Withdrawal Lifecycle") — once
 * an admin has approved it, the vendor can no longer unilaterally pull it
 * back.
 */
class CancelWithdrawalAction
{
    use LocksVendorWallet, RecordsWalletTransaction;

    public function handle(Withdrawal $withdrawal): Withdrawal
    {
        if ($withdrawal->status !== WithdrawalStatus::Pending) {
            throw new InvalidWithdrawalTransitionException('Only a pending withdrawal can be cancelled.');
        }

        return DB::transaction(function () use ($withdrawal) {
            $wallet = $this->lockedWallet($withdrawal->vendor);

            $this->recordTransaction($wallet, WalletTransactionType::WithdrawalReversed, WalletBalanceBucket::Reserved, -$withdrawal->amount, withdrawal: $withdrawal);
            $this->recordTransaction($wallet, WalletTransactionType::WithdrawalReversed, WalletBalanceBucket::Available, $withdrawal->amount, withdrawal: $withdrawal);

            $withdrawal->update(['status' => WithdrawalStatus::Cancelled]);

            return $withdrawal->fresh();
        });
    }
}
