<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;

class CurrencyController extends Controller
{
    public function switch(string $code): RedirectResponse
    {
        $currency = Currency::where('code', $code)->where('is_active', true)->firstOrFail();

        session(['display_currency' => $currency->code]);

        return back();
    }
}
