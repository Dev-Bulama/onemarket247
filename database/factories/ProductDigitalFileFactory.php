<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductDigitalFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductDigitalFile>
 */
class ProductDigitalFileFactory extends Factory
{
    protected $model = ProductDigitalFile::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory()->digital(),
            'name' => fake()->word().'.pdf',
            'file_path' => 'product-digital-files/'.fake()->uuid().'.pdf',
            'file_size' => fake()->numberBetween(1000, 5000000),
        ];
    }
}
