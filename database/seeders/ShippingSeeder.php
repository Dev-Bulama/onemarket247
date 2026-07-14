<?php

namespace Database\Seeders;

use App\Enums\ShippingRateType;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

/**
 * Every shipping resolution falls back to this zone if no
 * shipping_zone_locations row matches the destination (see
 * App\Actions\Shipping\ResolveShippingZoneAction) — a zone with no
 * locations at all is the "rest of world" catch-all, so a destination an
 * admin hasn't explicitly zoned yet still gets a real shipping cost
 * instead of failing checkout outright.
 */
class ShippingSeeder extends Seeder
{
    public function run(): void
    {
        if (ShippingZone::whereDoesntHave('locations')->exists()) {
            return;
        }

        $zone = ShippingZone::create([
            'name' => 'Rest of World',
            'is_active' => true,
            'sort_order' => 999,
        ]);

        ShippingRate::create([
            'shipping_zone_id' => $zone->id,
            'shipping_class_id' => null,
            'name' => 'Standard Shipping',
            'rate_type' => ShippingRateType::Flat,
            'base_amount' => 500,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
