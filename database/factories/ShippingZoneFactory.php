<?php

namespace Database\Factories;

use App\Models\ShippingZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZone>
 */
class ShippingZoneFactory extends Factory
{
    protected $model = ShippingZone::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Domestic', 'Regional', 'International']).' '.fake()->randomNumber(4),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
