<?php

namespace App\Actions\Location;

use App\Models\LocationConsent;
use App\Models\User;

/**
 * Toggles a user's opt-in to live location sharing (Priority 8 item 21).
 * Disabling consent doesn't erase existing App\Models\LocationPing rows
 * (those age out via App\Actions\Location\PruneLocationHistoryAction's
 * retention window instead) — it just stops the user appearing on the
 * admin live map and rejects any further pings via
 * RecordLocationPingAction until re-enabled.
 */
class UpdateLocationConsentAction
{
    public function handle(User $user, bool $enabled): LocationConsent
    {
        $consent = LocationConsent::firstOrNew(['user_id' => $user->id]);

        $consent->fill([
            'is_enabled' => $enabled,
            'enabled_at' => $enabled ? now() : $consent->enabled_at,
            'disabled_at' => $enabled ? null : now(),
        ])->save();

        return $consent->fresh();
    }
}
