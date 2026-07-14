<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active Language for this request — a session-persisted
 * choice from LocaleController::switch(), falling back to the active
 * default language — sets the real app locale (so validation messages,
 * date formatting, etc. all follow it) and shares it with every view so
 * layouts can render the correct dir="rtl|ltr" attribute driven by the
 * Language model instead of a hardcoded locale check.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = session('locale');

        $language = ($code ? Language::where('code', $code)->where('is_active', true)->first() : null)
            ?? Language::where('is_default', true)->where('is_active', true)->first()
            ?? Language::where('is_active', true)->first();

        if ($language) {
            App::setLocale($language->code);
        }

        View::share('currentLanguage', $language);

        return $next($request);
    }
}
