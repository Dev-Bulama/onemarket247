<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\AgentWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentWallet>
 */
class AgentWalletFactory extends Factory
{
    protected $model = AgentWallet::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'pending_balance' => 0,
            'available_balance' => 0,
            'reserved_balance' => 0,
            'withdrawn_balance' => 0,
        ];
    }
}
