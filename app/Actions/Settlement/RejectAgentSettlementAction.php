<?php

namespace App\Actions\Settlement;

use App\Actions\Wallet\Concerns\LocksAgentWallet;
use App\Actions\Wallet\Concerns\RecordsAgentWalletTransaction;
use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InvalidWithdrawalTransitionException;
use App\Models\AgentSettlement;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Mirrors RejectWithdrawalAction — releases the reserved funds back to
 * available_balance.
 */
class RejectAgentSettlementAction
{
    use LocksAgentWallet, RecordsAgentWalletTransaction;

    public function handle(AgentSettlement $settlement, string $reason, User $admin): AgentSettlement
    {
        if (! in_array($settlement->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true)) {
            throw new InvalidWithdrawalTransitionException('Only a pending or approved settlement can be rejected.');
        }

        return DB::transaction(function () use ($settlement, $reason, $admin) {
            $wallet = $this->lockedAgentWallet($settlement->agent);
            $before = $settlement->only(['status']);

            $this->recordAgentTransaction($wallet, WalletTransactionType::WithdrawalReversed, WalletBalanceBucket::Reserved, -$settlement->amount, settlement: $settlement);
            $this->recordAgentTransaction($wallet, WalletTransactionType::WithdrawalReversed, WalletBalanceBucket::Available, $settlement->amount, settlement: $settlement);

            $settlement->update([
                'status' => WithdrawalStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            AuditLogger::record('agent_settlement.rejected', $settlement, $before, $settlement->only(['status', 'rejection_reason']), $admin);

            return $settlement->fresh();
        });
    }
}
