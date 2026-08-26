<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Http\Resources\Api\V1\ProductResource;
use App\Http\Resources\Api\V1\StoreResource;
use App\Models\Product;
use App\Models\Store;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    use FiltersProducts;

    public function index(Request $request): JsonResponse
    {
        $stores = Store::query()
            ->where('status', '!=', StoreStatus::Inactive)
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return Paginated::response($stores, StoreResource::class);
    }

    public function show(string $slug): JsonResponse
    {
        $store = Store::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('status', '!=', StoreStatus::Inactive)
            ->with(['city', 'state', 'country'])
            ->firstOrFail();

        return ApiResponse::success(new StoreResource($store));
    }

    public function products(Request $request, string $slug): JsonResponse
    {
        $store = Store::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('status', '!=', StoreStatus::Inactive)
            ->firstOrFail();

        $products = $this->filteredProducts(Product::query()->where('vendor_id', $store->vendor_id), $request);

        return Paginated::response($products, ProductResource::class, [
            'store' => new StoreResource($store),
        ], key: 'products');
    }
}
