<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * API equivalent of SetLocale — a bearer-token API request carries no
 * session to read, so the mobile app's chosen language travels as an
 * `X-Language` header on every request instead of a session value.
 * Same fallback chain as the web version: chosen → default → any active.
 */
class SetApiLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->header('X-Language');

        $language = ($code ? Language::where('code', $code)->where('is_active', true)->first() : null)
            ?? Language::where('is_default', true)->where('is_active', true)->first()
            ?? Language::where('is_active', true)->first();

        if ($language) {
            App::setLocale($language->code);
        }

        return $next($request);
    }
}
