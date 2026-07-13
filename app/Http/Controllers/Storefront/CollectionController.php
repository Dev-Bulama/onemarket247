<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Models\Collection as ProductCollection;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CollectionController extends Controller
{
    use FiltersProducts;

    public function show(Request $request, ProductCollection $collection): View
    {
        $products = $this->filteredProducts(
            Product::query()->whereHas('collections', fn (Builder $q) => $q->where('collections.id', $collection->id)),
            $request,
        );

        return view('storefront.collections.show', [
            'collection' => $collection,
            'products' => $products,
            ...$this->filterOptions(),
        ]);
    }
}
