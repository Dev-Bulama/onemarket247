<?php

namespace Database\Factories;

use App\Models\ShippingCarrier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingCarrier>
 */
class ShippingCarrierFactory extends Factory
{
    protected $model = ShippingCarrier::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Logistics',
            'tracking_url_template' => 'https://example-tracking.test/track/{tracking_number}',
            'is_active' => true,
        ];
    }
}
