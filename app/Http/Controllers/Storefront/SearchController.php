<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    use FiltersProducts;

    public function index(Request $request): View
    {
        $term = trim((string) $request->string('q'));

        $query = Product::query();

        if ($term !== '') {
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        $products = $this->filteredProducts($query, $request);

        return view('storefront.search.index', [
            'term' => $term,
            'products' => $products,
            ...$this->filterOptions(),
        ]);
    }
}
