<?php

namespace App\Http\Controllers;

use App\Enums\StoreStatus;
use App\Models\Store;
use Illuminate\View\View;

class StoreController extends Controller
{
    public function show(string $slug): View
    {
        $store = Store::withoutGlobalScopes()
            ->where('slug', $slug)
            ->where('status', '!=', StoreStatus::Inactive)
            ->with('vendor')
            ->firstOrFail();

        return view('storefront.stores.show', ['store' => $store]);
    }
}
