<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExchangeRate>
 */
class ExchangeRateFactory extends Factory
{
    protected $model = ExchangeRate::class;

    public function definition(): array
    {
        return [
            'currency_id' => Currency::factory(),
            'rate' => fake()->randomFloat(6, 0.5, 1500),
            'is_manual' => false,
            'fetched_at' => now(),
        ];
    }
}
