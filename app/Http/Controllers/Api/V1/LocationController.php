<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Location\RecordLocationPingAction;
use App\Actions\Location\UpdateLocationConsentAction;
use App\Exceptions\LocationSharingDisabledException;
use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Priority 8 item 21 (Admin Live Location Tracking) — no mobile client
 * calls this yet (see the location-tracking feature's own scope decision:
 * the mobile app has no geolocation library installed, and adding one is
 * native mobile work outside what this sandbox can build or verify), but
 * the endpoints themselves are real and ready for a future phase to wire
 * up, exactly like App\Http\Controllers\Api\V1\DeviceTokenController's
 * own OneSignal player-id registration.
 */
class LocationController extends Controller
{
    public function updateConsent(Request $request): JsonResponse
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);

        $consent = app(UpdateLocationConsentAction::class)->handle($request->user(), $data['enabled']);

        return ApiResponse::success(['enabled' => $consent->is_enabled]);
    }

    public function storePing(Request $request): JsonResponse
    {
        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            app(RecordLocationPingAction::class)->handle(
                $request->user(),
                $data['latitude'],
                $data['longitude'],
                $data['accuracy'] ?? null,
            );
        } catch (LocationSharingDisabledException $exception) {
            return ApiResponse::error($exception->getMessage(), errorCode: 'LOCATION_SHARING_DISABLED');
        }

        return ApiResponse::success(message: 'Location recorded.', status: 201);
    }
}
