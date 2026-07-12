<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'label' => fake()->randomElement(['Home', 'Office']),
            'full_name' => fake()->name(),
            'phone' => fake()->e164PhoneNumber(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => null,
            'postal_code' => fake()->postcode(),
            'is_default_shipping' => false,
            'is_default_billing' => false,
        ];
    }
}
