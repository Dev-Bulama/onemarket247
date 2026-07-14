<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingZoneLocation>
 */
class ShippingZoneLocationFactory extends Factory
{
    protected $model = ShippingZoneLocation::class;

    public function definition(): array
    {
        return [
            'shipping_zone_id' => ShippingZone::factory(),
            'country_id' => Country::factory(),
            'state_id' => null,
            'city_id' => null,
        ];
    }
}
