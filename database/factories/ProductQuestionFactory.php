<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductQuestion>
 */
class ProductQuestionFactory extends Factory
{
    protected $model = ProductQuestion::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'customer_id' => User::factory(),
            'question' => fake()->sentence().'?',
            'is_answered' => false,
        ];
    }
}
