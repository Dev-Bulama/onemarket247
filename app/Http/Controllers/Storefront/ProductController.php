<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        if (! $product->isVisibleToCustomers()) {
            throw new NotFoundHttpException;
        }

        $product->load([
            'brand',
            'categories',
            'tags',
            'vendor.store',
            'variations' => fn ($query) => $query->where('is_active', true),
            'variations.attributeValues.attribute',
        ]);

        return view('storefront.products.show', ['product' => $product]);
    }
}
