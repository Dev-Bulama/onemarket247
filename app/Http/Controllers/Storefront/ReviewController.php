<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Review\SubmitReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ReviewRequest;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

class ReviewController extends Controller
{
    public function store(ReviewRequest $request, Product $product, SubmitReviewAction $action): RedirectResponse
    {
        Gate::authorize('create', ProductReview::class);

        try {
            $action->handle(
                $product,
                $request->user(),
                $request->integer('rating'),
                $request->string('title')->value() ?: null,
                $request->string('body')->value(),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }

        return back()->with('status', 'review-submitted');
    }
}
