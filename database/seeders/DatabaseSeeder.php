<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            PaymentGatewaySeeder::class,
            CommissionRuleSeeder::class,
            ShippingSeeder::class,
        ]);

        // HeroImageSeeder is intentionally NOT in this list: it pulls a
        // random photo from a public stock-photo service keyed by a text
        // seed, which has no real control over the photo's actual content
        // (it returned an unrelated wolf photo in production) — wrong for
        // the single most prominent brand image on the site. The hero
        // photo should be a real image the site owner uploads directly to
        // storage/app/public/hero/slide-1.jpg; nothing overwrites that
        // file automatically once it exists (see HeroImageSeeder::run()).

        // Plain create() rather than User::factory() — factories call fake()
        // (fakerphp/faker is require-dev only) which is unavailable when this
        // seeder runs on a production `composer install --no-dev` install.
        if (! User::where('email', 'admin@onemarket247.test')->exists()) {
            User::create([
                'name' => 'Test Super Admin',
                'email' => 'admin@onemarket247.test',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'user_type' => UserType::SuperAdmin,
                'status' => UserStatus::Active,
            ]);
        }
    }
}
