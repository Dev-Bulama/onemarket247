<?php

namespace App\Http\Controllers\Storefront;

use App\Actions\Product\SpotlightProductsAction;
use App\Enums\SpotlightDisplayArea;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    use FiltersProducts;

    public function index(Request $request, SpotlightProductsAction $spotlightProducts): View
    {
        $term = trim((string) $request->string('q'));

        $query = Product::query();

        if ($term !== '') {
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('categories', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        $products = $this->filteredProducts($query, $request);

        $spotlight = $spotlightProducts->handle(SpotlightDisplayArea::Search);

        return view('storefront.search.index', [
            'term' => $term,
            'products' => $products,
            'spotlightProducts' => $spotlight,
            ...$this->filterOptions(),
        ]);
    }
}
