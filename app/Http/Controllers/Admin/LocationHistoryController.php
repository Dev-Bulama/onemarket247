<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * A user's movement trail for the admin live map (Priority 8 item 21's
 * "view location history and movement trails for a configurable
 * period"). Every call is itself the audit trail Priority 8 asks for
 * ("record an audit trail of administrative access to ... historical
 * location data") — viewing someone's history is the sensitive action,
 * not a separate button.
 */
class LocationHistoryController extends Controller
{
    private const MAX_DAYS = 30;

    public function __invoke(Request $request, User $user): JsonResponse
    {
        $admin = Auth::guard('admin')->user();

        abort_unless($admin?->can('location_tracking.view'), 403);

        $days = min((int) $request->query('days', 1), self::MAX_DAYS);

        $pings = $user->locationPings()
            ->where('recorded_at', '>=', now()->subDays($days))
            ->orderBy('recorded_at')
            ->get(['latitude', 'longitude', 'recorded_at']);

        AuditLogger::record('location.history_viewed', $user, null, ['days' => $days], $admin);

        return response()->json([
            'data' => $pings->map(fn ($ping) => [
                'latitude' => (float) $ping->latitude,
                'longitude' => (float) $ping->longitude,
                'recorded_at' => $ping->recorded_at->toIso8601String(),
            ]),
        ]);
    }
}
