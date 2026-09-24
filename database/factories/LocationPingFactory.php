<?php

namespace Database\Factories;

use App\Models\LocationPing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationPing>
 */
class LocationPingFactory extends Factory
{
    protected $model = LocationPing::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'accuracy' => fake()->randomFloat(1, 5, 50),
            'recorded_at' => now(),
        ];
    }
}
