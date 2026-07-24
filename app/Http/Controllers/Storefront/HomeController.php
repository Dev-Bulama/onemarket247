<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Product\BestSellingProductsAction;
use App\Enums\ProductStatus;
use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(BestSellingProductsAction $bestSellingProducts): View
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
        ]);
    }

    /**
     * Products from vendors whose store is in the customer's selected
     * delivery city (falling back to state, then to generally popular
     * products) — genuinely location-driven from the session set by
     * LocationController::switch(), not a fabricated "near you" claim.
     */
    private function recommendedNearYou(): Collection
    {
        $cityId = session('delivery_location.city_id');
        $stateId = session('delivery_location.state_id');

        $base = fn () => Product::query()
            ->where('status', ProductStatus::Published)
            ->with(['brand', 'media', 'vendor.store.city'])
            ->withCount('approvedReviews');

        if ($cityId) {
            $matches = $base()->whereHas('vendor.store', fn ($q) => $q->where('city_id', $cityId))
                ->orderByDesc('view_count')->take(12)->get();

            if ($matches->isNotEmpty()) {
                return $matches;
            }
        }

        if ($stateId) {
            $matches = $base()->whereHas('vendor.store', fn ($q) => $q->where('state_id', $stateId))
                ->orderByDesc('view_count')->take(12)->get();

            if ($matches->isNotEmpty()) {
                return $matches;
            }
        }

        return $base()->orderByDesc('view_count')->take(12)->get();
    }
}
