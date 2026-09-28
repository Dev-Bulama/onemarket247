<?php

namespace App\Http\Middleware;

use App\Support\VisitorIdentifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarantees every 'web' request (not just the storefront/homepage
 * App\Http\Middleware\TrackSiteVisit already covers) can resolve a stable
 * anonymous visitor id — needed by
 * App\Actions\Disclaimer\ResolveActiveDisclaimerAction so a guest
 * submitting a vendor or agent application (routes outside the
 * 'track.visit' group) still has an identity to record disclaimer
 * acceptance against. Registered globally, ahead of route-level
 * middleware, so TrackSiteVisit can simply read the attribute this sets
 * rather than each independently minting a different id on the same
 * request.
 */
class ShareVisitorIdentifier
{
    public function handle(Request $request, Closure $next): Response
    {
        $hadCookie = $request->cookie(VisitorIdentifier::COOKIE) !== null;
        $visitorId = VisitorIdentifier::resolve($request);

        $request->attributes->set('visitor_id', $visitorId);

        $response = $next($request);

        if (! $hadCookie) {
            $response->headers->setCookie(cookie()->forever(VisitorIdentifier::COOKIE, $visitorId));
        }

        return $response;
    }
}
