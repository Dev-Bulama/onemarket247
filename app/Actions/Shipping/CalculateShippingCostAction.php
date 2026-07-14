<?php

namespace App\Actions\Shipping;

use App\Exceptions\ShippingUnavailableException;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ShippingRate;
use Illuminate\Support\Collection;

/**
 * Resolves the zone for the destination, then the most specific matching
 * rate within that zone: a rate scoped to the single shipping class shared
 * by every line item if one exists, otherwise the zone's general
 * (class-less) rate — same specificity-first shape as commission
 * resolution. Vendor orders whose items span more than one shipping class
 * fall back to the general rate, since a single shipment cost can't
 * unambiguously combine two class-specific rates.
 */
class CalculateShippingCostAction
{
    public function __construct(private readonly ResolveShippingZoneAction $resolveZone) {}

    /**
     * @param  Collection<int, array{sellable: Product|ProductVariation, quantity: int}>  $lines
     */
    public function handle(Collection $lines, int $subtotal, int $countryId, ?int $stateId, ?int $cityId): int
    {
        $zone = $this->resolveZone->handle($countryId, $stateId, $cityId);

        if (! $zone) {
            throw new ShippingUnavailableException('Shipping is not available to the selected address yet.');
        }

        $weightKg = $lines->sum(fn (array $line) => (float) ($line['sellable']->weight ?? 0) * $line['quantity']);

        $classIds = $lines->map(fn (array $line) => $this->shippingClassIdFor($line['sellable']))->unique()->filter();

        $rate = null;

        if ($classIds->count() === 1) {
            $rate = ShippingRate::where('shipping_zone_id', $zone->id)
                ->where('shipping_class_id', $classIds->first())
                ->where('is_active', true)
                ->first();
        }

        $rate ??= ShippingRate::where('shipping_zone_id', $zone->id)
            ->whereNull('shipping_class_id')
            ->where('is_active', true)
            ->first();

        if (! $rate) {
            throw new ShippingUnavailableException('No shipping rate is configured for this destination.');
        }

        return $rate->computeCost($weightKg, $subtotal);
    }

    private function shippingClassIdFor(Product|ProductVariation $sellable): ?int
    {
        return $sellable instanceof ProductVariation
            ? $sellable->product->shipping_class_id
            : $sellable->shipping_class_id;
    }
}
