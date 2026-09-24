<?php

namespace App\Actions\Wallet\Concerns;

use App\Models\Agent;
use App\Models\AgentWallet;

/**
 * Mirrors LocksVendorWallet: every wallet mutation must run inside
 * DB::transaction() and lock its AgentWallet row via SELECT ... FOR
 * UPDATE before reading/writing a balance column.
 */
trait LocksAgentWallet
{
    private function lockedAgentWallet(Agent $agent): AgentWallet
    {
        $wallet = AgentWallet::firstOrCreate(['agent_id' => $agent->id]);

        return AgentWallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();
    }
}
