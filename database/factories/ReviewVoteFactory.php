<?php

namespace Database\Factories;

use App\Models\ProductReview;
use App\Models\ReviewVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewVote>
 */
class ReviewVoteFactory extends Factory
{
    protected $model = ReviewVote::class;

    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'votable_type' => ProductReview::class,
            'votable_id' => ProductReview::factory(),
            'is_helpful' => true,
        ];
    }
}
