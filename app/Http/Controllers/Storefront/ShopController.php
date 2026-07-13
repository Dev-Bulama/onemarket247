<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    use FiltersProducts;

    public function index(Request $request): View
    {
        $products = $this->filteredProducts(Product::query(), $request);

        return view('storefront.shop.index', [
            'products' => $products,
            ...$this->filterOptions(),
        ]);
    }
}
