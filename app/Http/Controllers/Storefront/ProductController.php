<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        if (! $product->isVisibleToCustomers()) {
            throw new NotFoundHttpException;
        }

        $product->load([
            'brand',
            'categories',
            'tags',
            'vendor.store',
            'variations' => fn ($query) => $query->where('is_active', true),
            'variations.attributeValues.attribute',
            'approvedReviews' => fn ($query) => $query->latest()->with('customer'),
            'approvedReviews.votes',
            'questions' => fn ($query) => $query->where('is_answered', true)->latest()->with(['customer', 'answers.answeredBy']),
        ]);

        $user = auth()->user();

        return view('storefront.products.show', [
            'product' => $product,
            'userReview' => $user ? $product->reviews()->where('customer_id', $user->id)->first() : null,
        ]);
    }
}
