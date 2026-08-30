<?php

namespace App\Http\Middleware;

use App\Models\Currency;
use App\Support\PriceDisplay;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API equivalent of SetDisplayCurrency — the mobile app's chosen display
 * currency travels as an `X-Currency` header on every request instead of
 * a session value. Same fallback chain, and the same scope limit: this
 * only affects browse-time price display (Money::make() via
 * PriceDisplay::format()) — checkout/order totals stay in the store's
 * actual settlement currency regardless of this choice.
 */
class SetApiDisplayCurrency
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = $request->header('X-Currency');

        $currency = ($code ? Currency::where('code', $code)->where('is_active', true)->first() : null)
            ?? Currency::where('is_default', true)->where('is_active', true)->first()
            ?? Currency::where('is_active', true)->first();

        if ($currency) {
            PriceDisplay::setDisplayCurrency($currency);
        }

        return $next($request);
    }
}
