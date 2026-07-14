<?php

namespace App\Actions\Product;

use App\Enums\ProductStatus;
use App\Enums\VendorOrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Ranked by real units sold (order items on vendor orders that were
 * actually paid for and never cancelled), not just a manually curated
 * "featured" flag.
 */
class BestSellingProductsAction
{
    public function handle(int $limit): Collection
    {
        $productIds = OrderItem::query()
            ->whereHas('vendorOrder', fn ($query) => $query->whereNotIn('status', [
                VendorOrderStatus::PendingPayment, VendorOrderStatus::Cancelled,
            ]))
            ->whereNotNull('product_id')
            ->selectRaw('product_id, SUM(quantity) as units_sold')
            ->groupBy('product_id')
            ->orderByDesc('units_sold')
            ->take($limit)
            ->pluck('product_id');

        if ($productIds->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $productIds)
            ->where('status', ProductStatus::Published)
            ->with(['brand', 'media'])
            ->withCount('approvedReviews')
            ->get()
            ->sortBy(fn ($product) => array_search($product->id, $productIds->all()))
            ->values();
    }
}
