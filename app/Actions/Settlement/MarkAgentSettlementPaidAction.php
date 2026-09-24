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
 * Mirrors MarkWithdrawalPaidAction — confirms the payout actually
 * happened (a manual bank transfer outside this system, same as the
 * vendor withdrawal flow) and moves the reserved hold into
 * withdrawn_balance.
 */
class MarkAgentSettlementPaidAction
{
    use LocksAgentWallet, RecordsAgentWalletTransaction;

    public function handle(AgentSettlement $settlement, User $admin): AgentSettlement
    {
        if ($settlement->status !== WithdrawalStatus::Approved) {
            throw new InvalidWithdrawalTransitionException('Only an approved settlement can be marked paid.');
        }

        return DB::transaction(function () use ($settlement, $admin) {
            $wallet = $this->lockedAgentWallet($settlement->agent);
            $before = $settlement->only(['status']);

            $this->recordAgentTransaction($wallet, WalletTransactionType::WithdrawalPaid, WalletBalanceBucket::Reserved, -$settlement->amount, settlement: $settlement);
            $this->recordAgentTransaction($wallet, WalletTransactionType::WithdrawalPaid, WalletBalanceBucket::Withdrawn, $settlement->amount, settlement: $settlement);

            $settlement->update([
                'status' => WithdrawalStatus::Paid,
                'reviewed_by' => $settlement->reviewed_by ?? $admin->id,
                'paid_at' => now(),
            ]);

            AuditLogger::record('agent_settlement.paid', $settlement, $before, $settlement->only(['status']), $admin);

            return $settlement->fresh();
        });
    }
}
