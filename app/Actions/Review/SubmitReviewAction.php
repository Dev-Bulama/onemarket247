<?php

namespace App\Actions\Review;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use RuntimeException;

/**
 * Every review starts pending — purchase can't be verified yet
 * (order_items doesn't exist until Phase 12, see the product_reviews
 * migration), so moderation is the only gate available this phase.
 */
class SubmitReviewAction
{
    public function handle(Product $product, User $customer, int $rating, ?string $title, string $body): ProductReview
    {
        if ($product->reviews()->where('customer_id', $customer->id)->exists()) {
            throw new RuntimeException('You have already reviewed this product.');
        }

        return ProductReview::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'rating' => $rating,
            'title' => $title,
            'body' => $body,
            'status' => ReviewStatus::Pending,
        ]);
    }
}
