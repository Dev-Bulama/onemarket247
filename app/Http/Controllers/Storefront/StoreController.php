<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreController extends Controller
{
    use FiltersProducts;

    public function index(Request $request): View
    {
        $stores = Store::query()
            ->where('status', '!=', StoreStatus::Inactive)
            ->when($request->filled('q'), fn ($query) => $query->where('name', 'like', '%'.$request->string('q').'%'))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('storefront.stores.index', ['stores' => $stores]);
    }

    public function show(Request $request, string $slug): View
    {
        $store = Store::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('status', '!=', StoreStatus::Inactive)
            ->with('vendor')
            ->firstOrFail();

        $products = $this->filteredProducts(
            Product::query()->where('vendor_id', $store->vendor_id),
            $request,
        );

        return view('storefront.stores.show', [
            'store' => $store,
            'products' => $products,
            ...$this->filterOptions(),
        ]);
    }
}
