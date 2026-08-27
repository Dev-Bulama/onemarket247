<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $wishlist = $request->user()->wishlist()->first();

        $products = $wishlist ? $wishlist->products()->with('brand')->get() : collect();

        return ApiResponse::success(ProductResource::collection($products));
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $wishlist = $request->user()->wishlistOrCreate();

        if (! $wishlist->products()->where('product_id', $product->id)->exists()) {
            $wishlist->products()->attach($product);
        }

        return ApiResponse::success(message: 'Added to wishlist.', status: 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $wishlist = $request->user()->wishlist()->first();

        $wishlist?->products()->detach($product);

        return ApiResponse::success(message: 'Removed from wishlist.');
    }
}
