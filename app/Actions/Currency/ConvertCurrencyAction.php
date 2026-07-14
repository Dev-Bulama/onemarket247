<?php

namespace App\Actions\Currency;

use App\Models\Currency;

/**
 * Converts a minor-unit amount between currencies via the shared
 * default-currency basis: exchange_rates.rate is "units of this currency
 * per 1 unit of the default currency" (see ExchangeRateResource's own
 * helper text), so converting A -> B goes A -> default -> B. Accounts for
 * currencies with different decimal_places (e.g. 0 for JPY-style
 * currencies vs. 2 for USD-style) rather than assuming minor units are
 * directly comparable across currencies.
 */
class ConvertCurrencyAction
{
    public function handle(int $amountMinor, Currency $from, Currency $to): int
    {
        if ($from->id === $to->id) {
            return $amountMinor;
        }

        $fromMajor = $amountMinor / (10 ** $from->decimal_places);
        $fromRate = (float) ($from->exchangeRate?->rate ?? 1);
        $toRate = (float) ($to->exchangeRate?->rate ?? 1);

        $majorInDefaultCurrency = $fromMajor / $fromRate;
        $toMajor = $majorInDefaultCurrency * $toRate;

        return (int) round($toMajor * (10 ** $to->decimal_places));
    }
}
