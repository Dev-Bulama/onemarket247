<?php

namespace App\Actions\Settlement;

use App\Actions\Wallet\Concerns\LocksAgentWallet;
use App\Actions\Wallet\Concerns\RecordsAgentWalletTransaction;
use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Models\Agent;
use App\Models\AgentSettlement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Admin-initiated equivalent of RequestWithdrawalAction — an Agent has no
 * account to request its own payout from (see Agent's docblock), so an
 * admin creates the settlement on the agent's behalf. The requested
 * amount moves to reserved_balance immediately, inside the same locked
 * transaction that checks available_balance, mirroring the
 * over-withdrawal protection RequestWithdrawalAction relies on.
 */
class CreateAgentSettlementAction
{
    use LocksAgentWallet, RecordsAgentWalletTransaction;

    public function handle(Agent $agent, int $amount, User $admin): AgentSettlement
    {
        if ($amount <= 0) {
            throw new InsufficientWalletBalanceException('The settlement amount must be greater than zero.');
        }

        return DB::transaction(function () use ($agent, $amount, $admin) {
            $wallet = $this->lockedAgentWallet($agent);

            if ($wallet->available_balance < $amount) {
                throw new InsufficientWalletBalanceException('Not enough available balance to create this settlement.');
            }

            $settlement = AgentSettlement::create([
                'agent_id' => $agent->id,
                'amount' => $amount,
                'status' => WithdrawalStatus::Pending,
                'created_by' => $admin->id,
            ]);

            $this->recordAgentTransaction($wallet, WalletTransactionType::WithdrawalHold, WalletBalanceBucket::Available, -$amount, settlement: $settlement);
            $this->recordAgentTransaction($wallet, WalletTransactionType::WithdrawalHold, WalletBalanceBucket::Reserved, $amount, settlement: $settlement);

            return $settlement->fresh();
        });
    }
}
