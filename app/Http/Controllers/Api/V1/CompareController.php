<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $compareList = $request->user()->compareList()->first();

        $products = $compareList ? $compareList->products()->with(['brand', 'categories'])->get() : collect();

        return ApiResponse::success(ProductResource::collection($products));
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $compareList = $request->user()->compareListOrCreate();

        if (! $compareList->products()->where('product_id', $product->id)->exists()) {
            $compareList->products()->attach($product);
        }

        return ApiResponse::success(message: 'Added to compare list.', status: 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $compareList = $request->user()->compareList()->first();

        $compareList?->products()->detach($product);

        return ApiResponse::success(message: 'Removed from compare list.');
    }
}
