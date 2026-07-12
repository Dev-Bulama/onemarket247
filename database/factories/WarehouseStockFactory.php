<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WarehouseStock>
 */
class WarehouseStockFactory extends Factory
{
    protected $model = WarehouseStock::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'product_id' => Product::factory(),
            'product_variation_id' => null,
            'on_hand' => fake()->numberBetween(0, 200),
            'reserved' => 0,
            'damaged' => 0,
            'incoming' => 0,
        ];
    }

    public function forVariation(): static
    {
        return $this->state(fn () => [
            'product_id' => null,
            'product_variation_id' => ProductVariation::factory(),
        ]);
    }
}
