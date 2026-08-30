<?php

namespace App\Actions\Review;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Every review starts pending — purchase can't be verified yet
 * (order_items doesn't exist until Phase 12, see the product_reviews
 * migration), so moderation is the only gate available this phase.
 */
class SubmitReviewAction
{
    /**
     * @param  array<int, UploadedFile>  $images
     */
    public function handle(Product $product, User $customer, int $rating, ?string $title, string $body, array $images = []): ProductReview
    {
        if ($product->reviews()->where('customer_id', $customer->id)->exists()) {
            throw new RuntimeException('You have already reviewed this product.');
        }

        $review = ProductReview::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'rating' => $rating,
            'title' => $title,
            'body' => $body,
            'status' => ReviewStatus::Pending,
        ]);

        foreach ($images as $image) {
            $review->addMedia($image)->toMediaCollection('images');
        }

        return $review;
    }
}
