<?php

namespace Database\Factories;

use App\Enums\WithdrawalStatus;
use App\Models\Agent;
use App\Models\AgentSettlement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentSettlement>
 */
class AgentSettlementFactory extends Factory
{
    protected $model = AgentSettlement::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'amount' => fake()->numberBetween(5000, 100000),
            'status' => WithdrawalStatus::Pending,
        ];
    }
}
