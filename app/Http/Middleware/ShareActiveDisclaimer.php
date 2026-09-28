<?php

namespace App\Http\Middleware;

use App\Actions\Disclaimer\ResolveActiveDisclaimerAction;
use App\Support\VisitorIdentifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shares the one general-browsing disclaimer (if any — see
 * DisclaimerTrigger::generalTriggers()) every customer-facing page should
 * show, the same way App\Http\Middleware\ShareStorefrontNavigation shares
 * other site-wide view data. A page with its own specific checkpoint
 * disclaimer (checkout, vendor/agent application) passes that one
 * explicitly instead — see resources/views/partials/disclaimer-popup.blade.php,
 * which prefers a page-specific one over this general one so only a
 * single pop-up ever shows at once.
 */
class ShareActiveDisclaimer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();
        $guestIdentifier = $user ? null : ($request->attributes->get('visitor_id') ?? VisitorIdentifier::resolve($request));

        View::share('activeDisclaimer', app(ResolveActiveDisclaimerAction::class)->forGeneralBrowsing($user, $guestIdentifier));

        return $next($request);
    }
}
