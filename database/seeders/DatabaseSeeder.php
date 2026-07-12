<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            CountryStateCitySeeder::class,
            CurrencySeeder::class,
            LanguageSeeder::class,
            SettingsSeeder::class,
            VendorSubscriptionPlanSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test Super Admin',
            'email' => 'admin@onemarket247.test',
            'user_type' => UserType::SuperAdmin,
        ]);
    }
}
