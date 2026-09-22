<?php

namespace App\Actions\Product;

use App\Enums\ProductStatus;
use App\Enums\SpotlightDisplayArea;
use App\Models\ProductSpotlight;
use Illuminate\Support\Collection;

/**
 * Admin-curated spotlight products for a given display area (see
 * App\Models\ProductSpotlight) — shared by the web storefront and the
 * mobile/API home & category endpoints so both surfaces show the exact
 * same admin-scheduled picks, in the admin's chosen order.
 */
class SpotlightProductsAction
{
    public function handle(SpotlightDisplayArea $area, ?int $categoryId = null, int $limit = 12): Collection
    {
        return ProductSpotlight::query()
            ->active()
            ->forArea($area)
            ->when($categoryId !== null, fn ($query) => $query->where('category_id', $categoryId))
            ->whereHas('product', fn ($query) => $query->where('status', ProductStatus::Published))
            ->with([
                'product' => fn ($query) => $query->withCount('approvedReviews'),
                'product.brand', 'product.media', 'product.vendor.store',
            ])
            ->orderBy('position')
            ->take($limit)
            ->get()
            ->pluck('product')
            ->filter()
            ->values();
    }
}
