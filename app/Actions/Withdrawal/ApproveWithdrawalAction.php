<?php

namespace App\Actions\Withdrawal;

use App\Enums\WithdrawalStatus;
use App\Exceptions\InvalidWithdrawalTransitionException;
use App\Models\User;
use App\Models\Withdrawal;
use App\Support\AuditLogger;

/**
 * Marks a withdrawal as reviewed and approved; funds stay in
 * reserved_balance (already moved there at request time) until
 * MarkWithdrawalPaidAction confirms the payout actually happened.
 */
class ApproveWithdrawalAction
{
    public function handle(Withdrawal $withdrawal, User $admin): Withdrawal
    {
        if ($withdrawal->status !== WithdrawalStatus::Pending) {
            throw new InvalidWithdrawalTransitionException('Only a pending withdrawal can be approved.');
        }

        $before = $withdrawal->only(['status']);

        $withdrawal->update([
            'status' => WithdrawalStatus::Approved,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        AuditLogger::record('withdrawal.approved', $withdrawal, $before, $withdrawal->only(['status']), $admin);

        return $withdrawal->fresh();
    }
}
