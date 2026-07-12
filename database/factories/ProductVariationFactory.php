<?php

namespace Database\Factories;

use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariation>
 */
class ProductVariationFactory extends Factory
{
    protected $model = ProductVariation::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->variable(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-VAR-########')),
            'price' => fake()->numberBetween(500, 50000),
            'manage_stock' => true,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'stock_status' => StockStatus::InStock,
            'is_active' => true,
        ];
    }
}
