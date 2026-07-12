<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

/**
 * Seeds a starter currency set with static exchange rates against USD.
 * Automatic/live rate refresh is Phase 16 scope (see
 * docs/architecture/13-development-roadmap.md); these are development
 * defaults only.
 */
class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'symbol_position' => 'before', 'is_default' => true, 'rate' => 1.0],
            ['code' => 'NGN', 'name' => 'Nigerian Naira', 'symbol' => '₦', 'symbol_position' => 'before', 'is_default' => false, 'rate' => 1600.0],
            ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'symbol_position' => 'before', 'is_default' => false, 'rate' => 0.79],
            ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'symbol_position' => 'before', 'is_default' => false, 'rate' => 0.92],
            ['code' => 'AED', 'name' => 'UAE Dirham', 'symbol' => 'د.إ', 'symbol_position' => 'before', 'is_default' => false, 'rate' => 3.67],
            ['code' => 'GHS', 'name' => 'Ghanaian Cedi', 'symbol' => 'GH₵', 'symbol_position' => 'before', 'is_default' => false, 'rate' => 15.5],
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
