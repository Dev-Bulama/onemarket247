<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompareController extends Controller
{
    public function index(Request $request): View
    {
        $compareList = $request->user()->compareList()->first();

        return view('account.compare.index', [
            'products' => $compareList ? $compareList->products()->with(['brand', 'categories'])->get() : collect(),
        ]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $compareList = $request->user()->compareListOrCreate();

        if (! $compareList->products()->where('product_id', $product->id)->exists()) {
            $compareList->products()->attach($product);
        }

        return back()->with('status', 'compare-added');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $compareList = $request->user()->compareList()->first();

        $compareList?->products()->detach($product);

        return back()->with('status', 'compare-removed');
    }
}
