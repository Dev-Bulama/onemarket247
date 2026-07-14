<?php

namespace Database\Factories;

use App\Models\ShippingClass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShippingClass>
 */
class ShippingClassFactory extends Factory
{
    protected $model = ShippingClass::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Standard', 'Fragile', 'Bulky', 'Oversized']).' '.fake()->randomNumber(4);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
