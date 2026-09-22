<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Product\BestSellingProductsAction;
use App\Actions\Product\RecommendedNearYouAction;
use App\Actions\Product\SpotlightProductsAction;
use App\Enums\ProductStatus;
use App\Enums\SpotlightDisplayArea;
use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(BestSellingProductsAction $bestSellingProducts, SpotlightProductsAction $spotlightProducts): View
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
            ->with('vendor')
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

        $flashSaleEndsAt = $flashSaleProducts->min('flash_sale_ends_at');

        $recommendedNearYou = $this->recommendedNearYou();

        $spotlight = $spotlightProducts->handle(SpotlightDisplayArea::Homepage);

        return view('storefront.home', [
            'featuredProducts' => $featuredProducts,
            'newArrivals' => $newArrivals,
            'bestSellers' => $bestSellers,
            'trending' => $trending,
            'brands' => $brands,
            'stores' => $stores,
            'flashSaleProducts' => $flashSaleProducts,
            'flashSaleEndsAt' => $flashSaleEndsAt,
            'recommendedNearYou' => $recommendedNearYou,
            'spotlightProducts' => $spotlight,
        ]);
    }

    /**
     * Resolves city/state from the session set by LocationController::switch()
     * — see RecommendedNearYouAction for the actual query logic, shared
     * with the mobile API's /home endpoint.
     */
    private function recommendedNearYou(): Collection
    {
        return app(RecommendedNearYouAction::class)->handle(
            session('delivery_location.city_id'),
            session('delivery_location.state_id'),
        );
    }
}
