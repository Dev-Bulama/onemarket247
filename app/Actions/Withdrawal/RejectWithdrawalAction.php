<?php

namespace App\Actions\Withdrawal;

use App\Actions\Wallet\Concerns\LocksVendorWallet;
use App\Actions\Wallet\Concerns\RecordsWalletTransaction;
use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InvalidWithdrawalTransitionException;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Facades\DB;

/**
 * Releases the reserved funds back to available_balance — see
 * docs/architecture/09-lifecycles.md "Withdrawal Lifecycle" point 2.
 */
class RejectWithdrawalAction
{
    use LocksVendorWallet, RecordsWalletTransaction;

    public function handle(Withdrawal $withdrawal, string $reason, User $admin): Withdrawal
    {
        if (! in_array($withdrawal->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true)) {
            throw new InvalidWithdrawalTransitionException('Only a pending or approved withdrawal can be rejected.');
        }

        return DB::transaction(function () use ($withdrawal, $reason, $admin) {
            $wallet = $this->lockedWallet($withdrawal->vendor);

            $this->recordTransaction($wallet, WalletTransactionType::WithdrawalReversed, WalletBalanceBucket::Reserved, -$withdrawal->amount, withdrawal: $withdrawal);
            $this->recordTransaction($wallet, WalletTransactionType::WithdrawalReversed, WalletBalanceBucket::Available, $withdrawal->amount, withdrawal: $withdrawal);

            $withdrawal->update([
                'status' => WithdrawalStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            return $withdrawal->fresh();
        });
    }
}
