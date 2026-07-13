<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewVoteController extends Controller
{
    public function store(Request $request, ProductReview $review): RedirectResponse
    {
        abort_unless($review->status === ReviewStatus::Approved, 404);

        if ($review->votes()->where('customer_id', $request->user()->id)->exists()) {
            return back()->with('status', 'review-already-voted');
        }

        $review->votes()->create([
            'customer_id' => $request->user()->id,
            'is_helpful' => true,
        ]);

        $review->increment('helpful_count');

        return back()->with('status', 'review-vote-recorded');
    }
}
