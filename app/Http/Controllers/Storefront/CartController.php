<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Support\Cart\CartResolver;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(CartResolver $cartResolver): View
    {
        $cart = $cartResolver->resolve();

        $cart->load([
            'activeItems.product.brand',
            'activeItems.product.vendor.store',
            'activeItems.variation.attributeValues.attribute',
            'savedItems.product.brand',
            'savedItems.variation.attributeValues.attribute',
            'coupon',
        ]);

        $vendorGroups = $cart->activeItems->groupBy(fn ($item) => $item->product->vendor_id);

        return view('storefront.cart.index', [
            'cart' => $cart,
            'vendorGroups' => $vendorGroups,
        ]);
    }
}
