<?php

namespace Database\Factories;

use App\Enums\CommissionScopeType;
use App\Enums\CommissionType;
use App\Models\CommissionRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CommissionRule>
 */
class CommissionRuleFactory extends Factory
{
    protected $model = CommissionRule::class;

    public function definition(): array
    {
        return [
            'scope_type' => CommissionScopeType::Global,
            'rate_type' => CommissionType::Percentage,
            'rate_value' => fake()->randomFloat(2, 5, 20),
            'is_active' => true,
        ];
    }

    public function global(): static
    {
        return $this->state(fn () => [
            'scope_type' => CommissionScopeType::Global,
            'category_id' => null,
            'product_id' => null,
            'vendor_id' => null,
            'subscription_plan_id' => null,
        ]);
    }
}
