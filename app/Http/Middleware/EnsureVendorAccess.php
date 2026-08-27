<?php

namespace App\Http\Middleware;

use App\Enums\UserType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates /api/v1/vendor/* exactly like the Filament vendor panel does
 * (User::canAccessPanel('vendor')) — a vendor owner, or active staff of a
 * store, whose vendor account isn't suspended. Must run after
 * auth:sanctum so $request->user() is already resolved.
 */
class EnsureVendorAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user
                && in_array($user->user_type, [UserType::VendorOwner, UserType::VendorStaff], true)
                && $user->canAccessVendorDashboard(),
            403,
            'This account does not have vendor access.',
        );

        return $next($request);
    }
}
