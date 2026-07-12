<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\State;
use Illuminate\Database\Seeder;

/**
 * Seeds a small, real representative set of countries/states/cities so the
 * platform is usable in development without hand-maintaining full world
 * geography data (thousands of rows, out of scope for Phase 2). A production
 * deployment should import a complete geography dataset via the Phase 23
 * import tooling before go-live.
 */
class CountryStateCitySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            [
                'name' => 'Nigeria', 'iso2' => 'NG', 'iso3' => 'NGA', 'phone_code' => '234', 'currency_code' => 'NGN',
                'states' => [
                    'Lagos' => ['Ikeja', 'Lekki', 'Victoria Island'],
                    'Federal Capital Territory' => ['Abuja'],
                    'Kano' => ['Kano'],
                ],
            ],
            [
                'name' => 'United States', 'iso2' => 'US', 'iso3' => 'USA', 'phone_code' => '1', 'currency_code' => 'USD',
                'states' => [
                    'California' => ['Los Angeles', 'San Francisco'],
                    'New York' => ['New York City', 'Buffalo'],
                ],
            ],
            [
                'name' => 'United Kingdom', 'iso2' => 'GB', 'iso3' => 'GBR', 'phone_code' => '44', 'currency_code' => 'GBP',
                'states' => [
                    'England' => ['London', 'Manchester'],
                ],
            ],
            [
                'name' => 'United Arab Emirates', 'iso2' => 'AE', 'iso3' => 'ARE', 'phone_code' => '971', 'currency_code' => 'AED',
                'states' => [
                    'Dubai' => ['Dubai'],
                    'Abu Dhabi' => ['Abu Dhabi'],
                ],
            ],
            [
                'name' => 'Ghana', 'iso2' => 'GH', 'iso3' => 'GHA', 'phone_code' => '233', 'currency_code' => 'GHS',
                'states' => [
                    'Greater Accra' => ['Accra'],
                ],
            ],
        ];

        foreach ($countries as $countryData) {
            $country = Country::updateOrCreate(
                ['iso2' => $countryData['iso2']],
                [
                    'name' => $countryData['name'],
                    'iso3' => $countryData['iso3'],
                    'phone_code' => $countryData['phone_code'],
                    'currency_code' => $countryData['currency_code'],
                    'is_active' => true,
                ],
            );

            foreach ($countryData['states'] as $stateName => $cities) {
                $state = State::updateOrCreate(
                    ['country_id' => $country->id, 'name' => $stateName],
                    ['is_active' => true],
                );

                foreach ($cities as $cityName) {
                    City::updateOrCreate(
                        ['state_id' => $state->id, 'name' => $cityName],
                        ['country_id' => $country->id, 'is_active' => true],
                    );
                }
            }
        }
    }
}
