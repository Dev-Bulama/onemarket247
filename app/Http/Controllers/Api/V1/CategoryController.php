<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use FiltersProducts;

    public function index(): JsonResponse
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::success(CategoryResource::collection($categories));
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $descendantIds = $category->descendantIds();

        $products = $this->filteredProducts(
            Product::query()->whereHas('categories', fn (Builder $q) => $q->whereIn('categories.id', $descendantIds)),
            $request,
        );

        $subcategories = $category->children()->where('is_active', true)->orderBy('sort_order')->get();

        return Paginated::response($products, ProductResource::class, [
            'category' => new CategoryResource($category),
            'subcategories' => CategoryResource::collection($subcategories),
        ], key: 'products');
    }
}
