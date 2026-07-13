<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        $wishlist = $request->user()->wishlist()->first();

        return view('account.wishlist.index', [
            'products' => $wishlist ? $wishlist->products()->with('brand')->get() : collect(),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $wishlist = $request->user()->wishlistOrCreate();

        if (! $wishlist->products()->where('product_id', $product->id)->exists()) {
            $wishlist->products()->attach($product);
        }

        return back()->with('status', 'wishlist-added');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $wishlist = $request->user()->wishlist()->first();

        $wishlist?->products()->detach($product);

        return back()->with('status', 'wishlist-removed');
    }
}
