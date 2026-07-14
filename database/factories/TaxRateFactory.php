<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaxRate>
 */
class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    public function definition(): array
    {
        return [
            'tax_class_id' => null,
            'name' => 'Standard Rate',
            'country_id' => Country::factory(),
            'state_id' => null,
            'city_id' => null,
            'postal_code' => null,
            'rate_percent' => fake()->randomFloat(2, 5, 20),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
