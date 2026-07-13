<?php

namespace Database\Factories;

use App\Models\ProductAnswer;
use App\Models\ProductQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAnswer>
 */
class ProductAnswerFactory extends Factory
{
    protected $model = ProductAnswer::class;

    public function definition(): array
    {
        return [
            'product_question_id' => ProductQuestion::factory(),
            'answered_by' => User::factory(),
            'answer' => fake()->paragraph(),
        ];
    }
}
