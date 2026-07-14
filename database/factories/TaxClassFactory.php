<?php

namespace Database\Factories;

use App\Models\TaxClass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TaxClass>
 */
class TaxClassFactory extends Factory
{
    protected $model = TaxClass::class;

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Standard', 'Reduced', 'Zero-rated', 'Digital Goods']).' '.fake()->randomNumber(4);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
