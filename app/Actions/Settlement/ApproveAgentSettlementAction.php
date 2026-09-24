<?php

namespace App\Actions\Settlement;

use App\Enums\WithdrawalStatus;
use App\Exceptions\InvalidWithdrawalTransitionException;
use App\Models\AgentSettlement;
use App\Models\User;
use App\Support\AuditLogger;

/**
 * Mirrors ApproveWithdrawalAction — marks a settlement as reviewed and
 * approved; funds stay in reserved_balance until
 * MarkAgentSettlementPaidAction confirms the payout actually happened.
 */
class ApproveAgentSettlementAction
{
    public function handle(AgentSettlement $settlement, User $admin): AgentSettlement
    {
        if ($settlement->status !== WithdrawalStatus::Pending) {
            throw new InvalidWithdrawalTransitionException('Only a pending settlement can be approved.');
        }

        $before = $settlement->only(['status']);

        $settlement->update([
            'status' => WithdrawalStatus::Approved,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
        ]);

        AuditLogger::record('agent_settlement.approved', $settlement, $before, $settlement->only(['status']), $admin);

        return $settlement->fresh();
    }
}
