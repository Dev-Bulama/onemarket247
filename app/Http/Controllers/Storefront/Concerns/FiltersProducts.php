<?php

namespace App\Http\Controllers\Storefront\Concerns;

use App\Enums\ProductStatus;
use App\Enums\StockStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Shared category/brand/vendor/location/price/stock filtering, sorting, and
 * pagination for every storefront product listing (shop, category, brand,
 * collection, search, store). Every page using this trait is expected to
 * have already scoped $query to its own concern (e.g. a single category or
 * store) before calling filteredProducts() — this only applies the
 * *shopper* controls that are common across all of them.
 */
trait FiltersProducts
{
    protected function filteredProducts(Builder $query, Request $request): LengthAwarePaginator
    {
        $query->where('status', ProductStatus::Published);

        if ($categoryId = $request->integer('category_id')) {
            $query->whereHas('categories', fn (Builder $q) => $q->where('categories.id', $categoryId));
        }

        if ($brandId = $request->integer('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($vendorIds = $this->integerArray($request, 'vendor_id')) {
            $query->whereIn('vendor_id', $vendorIds);
        }

        $countryIds = $this->integerArray($request, 'country_id');
        $stateIds = $this->integerArray($request, 'state_id');
        $cityIds = $this->integerArray($request, 'city_id');

        if ($countryIds || $stateIds || $cityIds) {
            $query->whereHas('vendor.store', function (Builder $q) use ($countryIds, $stateIds, $cityIds) {
                if ($countryIds) {
                    $q->whereIn('country_id', $countryIds);
                }
                if ($stateIds) {
                    $q->whereIn('state_id', $stateIds);
                }
                if ($cityIds) {
                    $q->whereIn('city_id', $cityIds);
                }
            });
        }

        if ($request->filled('min_price')) {
            $minMinor = (int) round(((float) $request->input('min_price')) * 100);
            $query->where(function (Builder $q) use ($minMinor) {
                $q->where('price', '>=', $minMinor)
                    ->orWhereHas('variations', fn (Builder $vq) => $vq->where('price', '>=', $minMinor));
            });
        }

        if ($request->filled('max_price')) {
            $maxMinor = (int) round(((float) $request->input('max_price')) * 100);
            $query->where(function (Builder $q) use ($maxMinor) {
                $q->where('price', '<=', $maxMinor)
                    ->orWhereHas('variations', fn (Builder $vq) => $vq->where('price', '<=', $maxMinor));
            });
        }

        if ($request->boolean('in_stock')) {
            $query->where('stock_status', StockStatus::InStock);
        }

        if ($request->boolean('flash_sale')) {
            $query->onFlashSale();
        }

        match ($request->string('sort')->value()) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('published_at'),
        };

        return $query->with(['brand', 'media'])
            ->paginate(24)
            ->withQueryString();
    }

    /**
     * @return array{categories: Collection, brands: Collection, vendors: Collection, cities: Collection}
     */
    protected function filterOptions(): array
    {
        return [
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'vendors' => Vendor::query()
                ->whereHas('products', fn (Builder $q) => $q->where('status', ProductStatus::Published))
                ->orderBy('business_name')
                ->get(['id', 'business_name']),
            'cities' => City::query()
                ->whereIn('id', Store::query()
                    ->whereHas('vendor.products', fn (Builder $q) => $q->where('status', ProductStatus::Published))
                    ->whereNotNull('city_id')
                    ->pluck('city_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    /**
     * @return int[]
     */
    private function integerArray(Request $request, string $key): array
    {
        $value = $request->query($key);

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map('intval', $value)));
    }
}
