<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\PickupStation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PickupStation>
 */
class PickupStationFactory extends Factory
{
    protected $model = PickupStation::class;

    public function definition(): array
    {
        return [
            'vendor_id' => null,
            'name' => fake()->company().' Pickup Point',
            'phone' => fake()->phoneNumber(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'country_id' => Country::factory(),
            'state_id' => null,
            'city_id' => null,
            'is_active' => true,
        ];
    }
}
