<?php

namespace App\Actions\Tax;

use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Builder;

/**
 * Specificity-ordered tax rate lookup, combining two independent
 * specificity dimensions — location (postal code > city > state >
 * country) and tax class (the product's own class > the general/
 * class-less rate) — by trying each location tier from most to least
 * specific, and within each tier preferring a class-specific match before
 * falling back to the general one. Mirrors the same most-specific-first
 * pattern used by App\Actions\Commission\ResolveCommissionRuleAction and
 * App\Actions\Shipping\ResolveShippingZoneAction.
 */
class ResolveTaxRateAction
{
    public function handle(?int $taxClassId, int $countryId, ?int $stateId, ?int $cityId, ?string $postalCode): ?TaxRate
    {
        $tiers = [];

        if ($postalCode !== null) {
            $tiers[] = fn (Builder $query) => $query->where('country_id', $countryId)->where('postal_code', $postalCode);
        }

        if ($cityId !== null) {
            $tiers[] = fn (Builder $query) => $query->where('country_id', $countryId)->where('city_id', $cityId)->whereNull('postal_code');
        }

        if ($stateId !== null) {
            $tiers[] = fn (Builder $query) => $query->where('country_id', $countryId)->where('state_id', $stateId)->whereNull('city_id')->whereNull('postal_code');
        }

        $tiers[] = fn (Builder $query) => $query->where('country_id', $countryId)->whereNull('state_id')->whereNull('city_id')->whereNull('postal_code');

        foreach ($tiers as $constrain) {
            if ($taxClassId !== null) {
                $rate = $this->activeRateFor($constrain, $taxClassId);

                if ($rate) {
                    return $rate;
                }
            }

            $rate = $this->activeRateFor($constrain, null);

            if ($rate) {
                return $rate;
            }
        }

        return null;
    }

    private function activeRateFor(callable $constrain, ?int $taxClassId): ?TaxRate
    {
        $query = TaxRate::query()->where('is_active', true);

        if ($taxClassId !== null) {
            $query->where('tax_class_id', $taxClassId);
        } else {
            $query->whereNull('tax_class_id');
        }

        return $constrain($query)->orderBy('sort_order')->first();
    }
}
