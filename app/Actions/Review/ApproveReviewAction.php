<?php

namespace App\Actions\Review;

use App\Enums\ReviewStatus;
use App\Models\ProductReview;
use App\Models\User;

class ApproveReviewAction
{
    public function handle(ProductReview $review, ?User $moderator = null): ProductReview
    {
        $review->update([
            'status' => ReviewStatus::Approved,
            'rejection_reason' => null,
            'reviewed_by' => $moderator?->id,
            'reviewed_at' => now(),
        ]);

        return $review->fresh();
    }
}
