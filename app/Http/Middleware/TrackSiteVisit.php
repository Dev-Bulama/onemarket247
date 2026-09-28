<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use App\Models\Store;
use App\Support\VisitorIdentifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // App\Http\Middleware\ShareVisitorIdentifier, a global 'web'
        // middleware that runs ahead of this route-level one, has already
        // resolved (and, on a first visit, queued the cookie for) this
        // same id — reusing it here instead of minting a second one keeps
        // the SiteVisit row and the cookie the browser actually receives
        // in agreement.
        $visitorId = $request->attributes->get('visitor_id') ?? VisitorIdentifier::resolve($request);

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
