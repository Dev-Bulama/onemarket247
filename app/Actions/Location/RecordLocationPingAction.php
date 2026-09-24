<?php

namespace App\Actions\Location;

use App\Exceptions\LocationSharingDisabledException;
use App\Models\LocationConsent;
use App\Models\LocationPing;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Records one location fix — see routes/api.php's location endpoints
 * (the mobile client this is meant for doesn't exist yet; Priority 8's
 * scope decision was to build this API/admin half first). Rejects the
 * ping outright if the user hasn't opted in, so a stale or forged client
 * can never make someone appear on the admin live map without their
 * consent on file.
 */
class RecordLocationPingAction
{
    public function handle(User $user, float $latitude, float $longitude, ?float $accuracy = null, ?Carbon $recordedAt = null): LocationPing
    {
        $consent = LocationConsent::where('user_id', $user->id)->first();

        if (! $consent?->is_enabled) {
            throw new LocationSharingDisabledException('Location sharing is not enabled for this account.');
        }

        return LocationPing::create([
            'user_id' => $user->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'recorded_at' => $recordedAt ?? now(),
        ]);
    }
}
