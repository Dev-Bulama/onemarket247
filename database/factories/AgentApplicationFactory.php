<?php

namespace Database\Factories;

use App\Enums\AgentApplicationStatus;
use App\Models\AgentApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgentApplication>
 */
class AgentApplicationFactory extends Factory
{
    protected $model = AgentApplication::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'address' => fake()->address(),
            'status' => AgentApplicationStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(['status' => AgentApplicationStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => AgentApplicationStatus::Rejected, 'rejection_reason' => 'Incomplete documentation.']);
    }
}
