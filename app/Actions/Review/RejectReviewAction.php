<?php

namespace App\Actions\Review;

use App\Enums\ReviewStatus;
use App\Models\ProductReview;
use App\Models\User;
use App\Notifications\ReviewRejectedNotification;

class RejectReviewAction
{
    public function handle(ProductReview $review, string $reason, ?User $moderator = null): ProductReview
    {
        $review->update([
            'status' => ReviewStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by' => $moderator?->id,
            'reviewed_at' => now(),
        ]);

        $review->customer->notify(new ReviewRejectedNotification($review));

        return $review->fresh();
    }
}
