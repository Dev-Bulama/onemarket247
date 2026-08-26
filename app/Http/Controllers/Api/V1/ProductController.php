<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Http\Resources\Api\V1\ProductDetailResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    use FiltersProducts;

    public function index(Request $request): JsonResponse
    {
        $products = $this->filteredProducts(Product::query(), $request);

        return Paginated::response($products, ProductResource::class);
    }

    public function show(Product $product): JsonResponse
    {
        if (! $product->isVisibleToCustomers()) {
            throw new NotFoundHttpException;
        }

        $product->load([
            'brand',
            'categories',
            'vendor.store',
            'variations' => fn ($query) => $query->where('is_active', true),
            'variations.attributeValues.attribute',
            'approvedReviews',
        ]);

        return ApiResponse::success(new ProductDetailResource($product));
    }
}
