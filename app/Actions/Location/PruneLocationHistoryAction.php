<?php

namespace App\Actions\Location;

use App\Models\LocationPing;
use App\Models\Setting;

/**
 * Enforces the location-history retention window (Priority 8 item 21's
 * "appropriate data-retention policies") — run daily by the
 * location:prune-history console command. The admin-configurable
 * `location.history_retention_days` setting mirrors how
 * `finance.minimum_withdrawal` already drives withdrawal rules.
 */
class PruneLocationHistoryAction
{
    private const DEFAULT_RETENTION_DAYS = 30;

    public function handle(): int
    {
        $days = (int) (Setting::where('key', 'location.history_retention_days')->first()?->typed_value ?? self::DEFAULT_RETENTION_DAYS);

        return LocationPing::where('recorded_at', '<', now()->subDays($days))->delete();
    }
}
