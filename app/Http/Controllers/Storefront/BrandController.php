<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    use FiltersProducts;

    public function index(): View
    {
        $brands = Brand::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('storefront.brands.index', ['brands' => $brands]);
    }

    public function show(Request $request, Brand $brand): View
    {
        $products = $this->filteredProducts(
            Product::query()->where('brand_id', $brand->id),
            $request,
        );

        return view('storefront.brands.show', [
            'brand' => $brand,
            'products' => $products,
            ...$this->filterOptions(),
        ]);
    }
}
