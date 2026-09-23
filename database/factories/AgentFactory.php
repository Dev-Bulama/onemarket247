<?php

namespace Database\Factories;

use App\Enums\AgentStatus;
use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'address' => fake()->address(),
            'status' => AgentStatus::Approved,
            'approved_at' => now(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => AgentStatus::Suspended, 'suspended_at' => now()]);
    }

    public function deactivated(): static
    {
        return $this->state(['status' => AgentStatus::Deactivated]);
    }
}
