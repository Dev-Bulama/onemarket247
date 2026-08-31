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
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Confirms the payout actually happened (the payout itself is a manual
 * bank transfer outside this system — see the Phase 14 completion
 * report's scope decision on this). Moves the reserved hold into
 * withdrawn_balance — see docs/architecture/09-lifecycles.md "Withdrawal
 * Lifecycle" point 3.
 */
class MarkWithdrawalPaidAction
{
    use LocksVendorWallet, RecordsWalletTransaction;

    public function handle(Withdrawal $withdrawal, User $admin): Withdrawal
    {
        if ($withdrawal->status !== WithdrawalStatus::Approved) {
            throw new InvalidWithdrawalTransitionException('Only an approved withdrawal can be marked paid.');
        }

        return DB::transaction(function () use ($withdrawal, $admin) {
            $wallet = $this->lockedWallet($withdrawal->vendor);
            $before = $withdrawal->only(['status']);

            $this->recordTransaction($wallet, WalletTransactionType::WithdrawalPaid, WalletBalanceBucket::Reserved, -$withdrawal->amount, withdrawal: $withdrawal);
            $this->recordTransaction($wallet, WalletTransactionType::WithdrawalPaid, WalletBalanceBucket::Withdrawn, $withdrawal->amount, withdrawal: $withdrawal);

            $withdrawal->update([
                'status' => WithdrawalStatus::Paid,
                'reviewed_by' => $withdrawal->reviewed_by ?? $admin->id,
                'paid_at' => now(),
            ]);

            AuditLogger::record('withdrawal.paid', $withdrawal, $before, $withdrawal->only(['status']), $admin);

            return $withdrawal->fresh();
        });
    }
}
