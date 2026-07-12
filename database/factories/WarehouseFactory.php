<?php

namespace Database\Factories;

use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    public function definition(): array
    {
        $name = fake()->unique()->city().' Warehouse';

        return [
            'vendor_id' => Vendor::factory(),
            'name' => $name,
            'code' => Str::upper(Str::slug($name, '')).'-'.fake()->unique()->numberBetween(1, 99999),
            'address' => fake()->address(),
            'is_default' => false,
            'is_active' => true,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
