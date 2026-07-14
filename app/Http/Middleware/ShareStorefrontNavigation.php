<?php

namespace App\Http\Middleware;

use App\Enums\ProductStatus;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Product;
use App\Models\Setting;
use App\Models\State;
use App\Support\Cart\CartResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares data every storefront page needs (nav categories, product count,
 * geography for the location selector, language/currency switchers, cart
 * count) via View::share() rather than a view composer bound to
 * layouts.storefront — a composer's data isn't available inside a child
 * view's own @include()s, which render before the parent layout (and its
 * composer) does. Middleware runs before any view rendering starts, so
 * this is available everywhere regardless of render order.
 */
class ShareStorefrontNavigation
{
    public function handle(Request $request, Closure $next): Response
    {
        View::share('cartItemCount', app(CartResolver::class)->peek()?->activeItems()->sum('quantity') ?? 0);
        View::share('switchableLanguages', Language::where('is_active', true)->orderBy('name')->get());
        View::share('switchableCurrencies', Currency::where('is_active', true)->orderBy('code')->get());
        View::share('announcementText', Setting::where('key', 'storefront.announcement_text')->first()?->typed_value);
        View::share('navCategories', Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get());
        View::share('totalProductCount', Product::where('status', ProductStatus::Published)->count());
        View::share('allCountries', Country::where('is_active', true)->orderBy('name')->get(['id', 'name']));
        View::share('allStates', State::where('is_active', true)->orderBy('name')->get(['id', 'country_id', 'name']));
        View::share('allCities', City::where('is_active', true)->orderBy('name')->get(['id', 'state_id', 'name']));

        return $next($request);
    }
}
