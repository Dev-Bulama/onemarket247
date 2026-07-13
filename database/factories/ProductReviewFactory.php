<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductReview>
 */
class ProductReviewFactory extends Factory
{
    protected $model = ProductReview::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'customer_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => ReviewStatus::Pending,
            'is_verified_purchase' => false,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => ReviewStatus::Approved]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['status' => ReviewStatus::Rejected]);
    }
}
