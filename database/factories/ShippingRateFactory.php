<?php

namespace Database\Factories;

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingRate>
 */
class ShippingRateFactory extends Factory
{
    protected $model = ShippingRate::class;

    public function definition(): array
    {
        return [
            'shipping_zone_id' => ShippingZone::factory(),
            'shipping_class_id' => null,
            'name' => 'Standard Shipping',
            'rate_type' => ShippingRateType::Flat,
            'base_amount' => fake()->numberBetween(300, 2000),
            'per_kg_amount' => null,
            'free_shipping_min_amount' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function perWeight(): static
    {
        return $this->state(fn () => [
            'rate_type' => ShippingRateType::PerWeight,
            'base_amount' => fake()->numberBetween(100, 500),
            'per_kg_amount' => fake()->numberBetween(50, 300),
        ]);
    }

    public function free(): static
    {
        return $this->state(fn () => [
            'rate_type' => ShippingRateType::Free,
            'base_amount' => 0,
            'per_kg_amount' => null,
        ]);
    }
}
