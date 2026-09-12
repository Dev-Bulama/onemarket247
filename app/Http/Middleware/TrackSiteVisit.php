<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Records one SiteVisit row per storefront pageview, for the admin/vendor
 * analytics dashboards (visitor counts, locations, vendor-scoped store
 * traffic). Deliberately applied only to routes/storefront.php + the
 * homepage — NOT the admin/vendor Filament panels, NOT the API/mobile app
 * — see the route registrations in routes/web.php.
 *
 * country_code is always written as null here: resolving it requires an
 * external HTTP call per IP, and doing that inline on every pageview would
 * add real latency to storefront browsing. It's filled in later, out of
 * band, by the ResolveSiteVisitCountries scheduled command.
 */
class TrackSiteVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $response->getStatusCode() >= 400) {
            return $response;
        }

        $visitorId = $request->cookie('visitor_id') ?? (string) Str::uuid();

        try {
            SiteVisit::create([
                'visitor_id' => $visitorId,
                'user_id' => Auth::guard('web')->id(),
                'vendor_id' => $this->resolveVendorId($request),
                'path' => $request->path(),
                'ip_address' => $request->ip(),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

        if (! $request->cookie('visitor_id')) {
            $response->headers->setCookie(cookie()->forever('visitor_id', $visitorId));
        }

        return $response;
    }

    private function resolveVendorId(Request $request): ?int
    {
        if ($request->route()?->getName() !== 'stores.show') {
            return null;
        }

        return Store::withoutGlobalScopes()
            ->where('slug', $request->route('slug'))
            ->value('vendor_id');
    }
}
