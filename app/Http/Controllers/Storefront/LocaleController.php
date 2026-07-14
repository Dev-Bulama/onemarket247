<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function switch(string $code): RedirectResponse
    {
        $language = Language::where('code', $code)->where('is_active', true)->firstOrFail();

        session(['locale' => $language->code]);

        return back();
    }
}
