<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductTranslation>
 */
class ProductTranslationFactory extends Factory
{
    protected $model = ProductTranslation::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'language_id' => Language::factory(),
            'name' => fake()->words(3, true),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'seo_title' => fake()->sentence(4),
            'seo_description' => fake()->sentence(),
        ];
    }
}
