<?php

namespace App\Actions\Shipping;

use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;

/**
 * Specificity-ordered zone lookup: city match > state match (zone-wide,
 * no city set on the location row) > country match (zone-wide, no state
 * or city set) — mirroring the same most-specific-first pattern
 * App\Actions\Commission\ResolveCommissionRuleAction uses for commission
 * tiers. Falls back to a zone with no locations at all as a "rest of
 * world" catch-all (seeded by ShippingSeeder), since neither the ERD nor
 * models doc names an explicit worldwide-zone column.
 */
class ResolveShippingZoneAction
{
    public function handle(int $countryId, ?int $stateId, ?int $cityId): ?ShippingZone
    {
        if ($cityId !== null) {
            $zone = $this->activeZoneFor(fn ($query) => $query->where('country_id', $countryId)->where('city_id', $cityId));

            if ($zone) {
                return $zone;
            }
        }

        if ($stateId !== null) {
            $zone = $this->activeZoneFor(fn ($query) => $query->where('country_id', $countryId)->where('state_id', $stateId)->whereNull('city_id'));

            if ($zone) {
                return $zone;
            }
        }

        $zone = $this->activeZoneFor(fn ($query) => $query->where('country_id', $countryId)->whereNull('state_id')->whereNull('city_id'));

        return $zone ?? ShippingZone::where('is_active', true)
            ->whereDoesntHave('locations')
            ->orderBy('sort_order')
            ->first();
    }

    private function activeZoneFor(callable $constrain): ?ShippingZone
    {
        $query = ShippingZoneLocation::query()->whereHas('zone', fn ($q) => $q->where('is_active', true));

        $location = $constrain($query)->first();

        return $location?->zone;
    }
}
