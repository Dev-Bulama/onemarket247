<?php

namespace App\Actions\Review;

use App\Enums\ReviewStatus;
use App\Models\ProductReview;
use App\Models\User;
use App\Notifications\ReviewRejectedNotification;
use Throwable;

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

        // The rejection already persisted above — a mail transport failure
        // here must not turn a successful moderation action into a 500.
        try {
            $review->customer->notify(new ReviewRejectedNotification($review));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $review->fresh();
    }
}
