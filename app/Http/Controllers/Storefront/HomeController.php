<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Product\BestSellingProductsAction;
use App\Enums\ProductStatus;
use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\Store;
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

        return view('storefront.home', [
            'featuredProducts' => $featuredProducts,
            'newArrivals' => $newArrivals,
            'bestSellers' => $bestSellers,
            'trending' => $trending,
            'brands' => $brands,
            'stores' => $stores,
        ]);
    }
}
