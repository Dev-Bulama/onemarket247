<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Http\Resources\Api\V1\BrandResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Brand;
use App\Models\Product;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use FiltersProducts;

    public function index(): JsonResponse
    {
        $brands = Brand::query()->where('is_active', true)->orderBy('name')->get();

        return ApiResponse::success(BrandResource::collection($brands));
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $brand = Brand::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $products = $this->filteredProducts(Product::query()->where('brand_id', $brand->id), $request);

        return Paginated::response($products, ProductResource::class, [
            'brand' => new BrandResource($brand),
        ], key: 'products');
    }
}
