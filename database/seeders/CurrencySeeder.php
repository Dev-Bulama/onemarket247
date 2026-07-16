<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

/**
 * Seeds a starter currency set with static exchange rates against NGN, the
 * marketplace's default/settlement currency (see App\Support\PriceDisplay —
 * every monetary column is stored in whichever currency is flagged
 * is_default). exchange_rates.rate is "units of this currency per 1 unit of
 * the default currency" (see ConvertCurrencyAction), so NGN itself is 1.0
 * and every other currency is expressed relative to it. Automatic/live rate
 * refresh is Phase 16 scope (see docs/architecture/13-development-roadmap.md);
 * these are static defaults only — update them with real rates as needed.
 */
class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => '₦', 'symbol_position' => 'before', 'is_default' => true, 'rate' => 1.0],
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'symbol_position' => 'before', 'is_default' => false, 'rate' => 1 / 1600],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'symbol_position' => 'before', 'is_default' => false, 'rate' => 0.79 / 1600],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'symbol_position' => 'before', 'is_default' => false, 'rate' => 0.92 / 1600],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ', 'symbol_position' => 'before', 'is_default' => false, 'rate' => 3.67 / 1600],
            ['code' => 'GHS', 'name' => 'Ghanaian Cedi', 'symbol' => 'GH₵', 'symbol_position' => 'before', 'is_default' => false, 'rate' => 15.5 / 1600],
        ];

        foreach ($currencies as $data) {
            $currency = Currency::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'symbol' => $data['symbol'],
                    'symbol_position' => $data['symbol_position'],
                    'decimal_places' => 2,
                    'thousand_separator' => ',',
                    'decimal_separator' => '.',
                    'is_default' => $data['is_default'],
                    'is_active' => true,
                ],
            );

            ExchangeRate::updateOrCreate(
                ['currency_id' => $currency->id],
                ['rate' => $data['rate'], 'is_manual' => true, 'fetched_at' => now()],
            );
        }
    }
}
