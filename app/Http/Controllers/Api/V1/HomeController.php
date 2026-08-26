<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Product\BestSellingProductsAction;
use App\Actions\Product\RecommendedNearYouAction;
use App\Enums\ProductStatus;
use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BrandResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\StoreResource;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Store;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mirrors App\Http\Controllers\Storefront\HomeController's sections
 * exactly, so the mobile home screen and the web homepage are always
 * built from the same queries. The one deliberate difference: "near you"
 * takes city_id/state_id as query params instead of reading the session
 * — a bearer-token API request carries no session to read.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request, BestSellingProductsAction $bestSellingProducts, RecommendedNearYouAction $recommendedNearYou): JsonResponse
    {
        $featuredProducts = Product::query()
            ->where('status', ProductStatus::Published)
            ->where('is_featured', true)
            ->with(['brand', 'media'])
            ->withCount('approvedReviews')
            ->latest('published_at')
            ->take(12)
            ->get();

        $newArrivals = Product::query()
            ->where('status', ProductStatus::Published)
            ->with(['brand', 'media'])
            ->withCount('approvedReviews')
            ->latest('published_at')
            ->take(12)
            ->get();

        $bestSellers = $bestSellingProducts->handle(12);

        $trending = Product::query()
            ->where('status', ProductStatus::Published)
            ->with(['brand', 'media'])
            ->withCount('approvedReviews')
            ->orderByDesc('view_count')
            ->take(8)
            ->get();

        $brands = Brand::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->take(12)
            ->get();

        $stores = Store::query()
            ->where('status', StoreStatus::Active)
            ->where('is_featured', true)
            ->take(4)
            ->get();

        $flashSaleProducts = Product::query()
            ->where('status', ProductStatus::Published)
            ->onFlashSale()
            ->with(['brand', 'media'])
            ->withCount('approvedReviews')
            ->orderBy('flash_sale_ends_at')
            ->take(12)
            ->get();

        $recommendedNearYouProducts = $recommendedNearYou->handle(
            $request->integer('city_id') ?: null,
            $request->integer('state_id') ?: null,
        );

        return ApiResponse::success([
            'featured_products' => ProductResource::collection($featuredProducts),
            'new_arrivals' => ProductResource::collection($newArrivals),
            'best_sellers' => ProductResource::collection($bestSellers),
            'trending' => ProductResource::collection($trending),
            'flash_sale' => [
                'products' => ProductResource::collection($flashSaleProducts),
                'ends_at' => $flashSaleProducts->min('flash_sale_ends_at'),
            ],
            'recommended_near_you' => ProductResource::collection($recommendedNearYouProducts),
            'brands' => BrandResource::collection($brands),
            'stores' => StoreResource::collection($stores),
        ]);
    }
}
