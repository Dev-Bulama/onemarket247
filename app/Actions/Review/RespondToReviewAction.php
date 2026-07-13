<?php

namespace App\Actions\Review;

use App\Models\ProductReview;

class RespondToReviewAction
{
    public function handle(ProductReview $review, string $response): ProductReview
    {
        $review->update([
            'vendor_response' => $response,
            'vendor_responded_at' => now(),
        ]);

        return $review->fresh();
    }
}
