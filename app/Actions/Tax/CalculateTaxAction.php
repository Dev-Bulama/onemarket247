<?php

namespace App\Actions\Tax;

use App\Models\TaxRate;

/**
 * Unlike shipping (where "no rate configured" rejects checkout),
 * a destination/product combination with no matching tax rate is a
 * legitimate real-world state (tax-exempt jurisdiction, no nexus, etc.)
 * — it simply means zero tax, not a checkout failure.
 */
class CalculateTaxAction
{
    public function __construct(private readonly ResolveTaxRateAction $resolveRate) {}

    /**
     * @return array{rate: ?TaxRate, taxAmount: int}
     */
    public function handle(int $taxableAmount, ?int $taxClassId, int $countryId, ?int $stateId, ?int $cityId, ?string $postalCode): array
    {
        $rate = $this->resolveRate->handle($taxClassId, $countryId, $stateId, $cityId, $postalCode);

        return [
            'rate' => $rate,
            'taxAmount' => $rate?->computeTax($taxableAmount) ?? 0,
        ];
    }
}
