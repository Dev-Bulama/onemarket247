<?php

namespace Database\Factories;

use App\Enums\BillingPeriod;
use App\Models\VendorSubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<VendorSubscriptionPlan>
 */
class VendorSubscriptionPlanFactory extends Factory
{
    protected $model = VendorSubscriptionPlan::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true).' Plan';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->randomElement([0, 2900, 9900]),
            'billing_period' => BillingPeriod::Monthly,
            'max_products' => fake()->randomElement([10, 100, null]),
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function free(): static
    {
        return $this->state(fn () => ['price' => 0, 'is_default' => true]);
    }
}
