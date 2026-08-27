<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Review\SubmitReviewAction;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ReviewRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Product;
use App\Models\ProductReview;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class ReviewController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $reviews = $product->approvedReviews()
            ->with('customer')
            ->latest()
            ->paginate(20);

        return Paginated::response($reviews, ReviewResource::class);
    }

    public function store(ReviewRequest $request, Product $product, SubmitReviewAction $action): JsonResponse
    {
        Gate::authorize('create', ProductReview::class);

        try {
            $review = $action->handle(
                $product,
                $request->user(),
                $request->integer('rating'),
                $request->string('title')->value() ?: null,
                $request->string('body')->value(),
            );
        } catch (RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), ['review' => [$e->getMessage()]], 'REVIEW_ERROR');
        }

        return ApiResponse::success(new ReviewResource($review), status: 201);
    }

    public function markHelpful(Request $request, ProductReview $review): JsonResponse
    {
        abort_unless($review->status === ReviewStatus::Approved, 404);

        if ($review->votes()->where('customer_id', $request->user()->id)->exists()) {
            return ApiResponse::error('You already marked this review as helpful.', [], 'ALREADY_VOTED');
        }

        $review->votes()->create(['customer_id' => $request->user()->id, 'is_helpful' => true]);
        $review->increment('helpful_count');

        return ApiResponse::success(['helpful_count' => $review->fresh()->helpful_count]);
    }
}
