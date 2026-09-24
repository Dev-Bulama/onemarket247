<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Backs the admin Live Location Tracking page's map (Priority 8 item 21)
 * — polled every few seconds by the page's own JS rather than routed
 * through Livewire, so the map keeps updating with plain fetch() calls
 * independent of any Livewire round-trip. Only customers and vendor
 * owners/staff can ever appear here: agents and delivery partners were
 * both deliberately built with no login-capable app (see Priority 5 and
 * 7's own scope decisions), so there's no device of theirs that could
 * ever report a location.
 */
class LiveLocationDataController extends Controller
{
    private const ONLINE_THRESHOLD_MINUTES = 5;

    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(Auth::guard('admin')->user()?->can('location_tracking.view'), 403);

        $query = User::query()
            ->whereHas('locationConsent', fn ($q) => $q->where('is_enabled', true))
            ->whereHas('locationPings')
            ->whereIn('user_type', [UserType::Customer, UserType::VendorOwner, UserType::VendorStaff])
            ->with(['locationPings' => fn ($q) => $q->latestPerUser()]);

        if ($type = $request->query('type')) {
            $query->where('user_type', $type);
        }

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }

        $onlineThreshold = now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES);
        $onlineOnly = $request->boolean('online_only');

        $markers = $query->get()
            ->map(function (User $user) use ($onlineThreshold) {
                $ping = $user->locationPings->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'type' => $user->user_type->getLabel(),
                    'latitude' => (float) $ping->latitude,
                    'longitude' => (float) $ping->longitude,
                    'recorded_at' => $ping->recorded_at->diffForHumans(),
                    'is_online' => $ping->recorded_at->greaterThanOrEqualTo($onlineThreshold),
                ];
            })
            ->when($onlineOnly, fn ($collection) => $collection->where('is_online', true))
            ->values();

        return response()->json(['data' => $markers]);
    }
}
