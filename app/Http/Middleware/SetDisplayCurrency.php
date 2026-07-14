<?php

namespace App\Http\Middleware;

use App\Models\Currency;
use App\Support\PriceDisplay;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the customer's preferred display currency for browse-time
 * price conversion (see App\Support\PriceDisplay) — a session-persisted
 * choice from CurrencyController::switch(), falling back to the active
 * default currency. Checkout/order/cart totals are deliberately left in
 * the store's actual settlement currency regardless of this choice (see
 * docs/reports/phase-16-completion-report.md) — this only affects
 * browse-time product price display.
 */
class SetDisplayCurrency
{
    public function handle(Request $request, Closure $next): Response
    {
        $code = session('display_currency');

        $currency = ($code ? Currency::where('code', $code)->where('is_active', true)->first() : null)
            ?? Currency::where('is_default', true)->where('is_active', true)->first()
            ?? Currency::where('is_active', true)->first();

        if ($currency) {
            PriceDisplay::setDisplayCurrency($currency);
        }

        View::share('displayCurrency', $currency);

        return $next($request);
    }
}
