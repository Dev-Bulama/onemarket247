<?php

namespace Database\Seeders;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
        // the single most prominent brand image on the site. Hero photos
        // should be real images the site owner uploads through the admin
        // "Hero Slides" resource; HeroImageSeeder only fills in stock
        // photos when no HeroSlide rows exist at all (see its run()).

        // Plain create() rather than User::factory() — factories call fake()
        // (fakerphp/faker is require-dev only) which is unavailable when this
        // seeder runs on a production `composer install --no-dev` install.
        $admin = User::firstOrCreate(
            ['email' => 'admin@onemarket247.test'],
            [
                'name' => 'Test Super Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'user_type' => UserType::SuperAdmin,
                'status' => UserStatus::Active,
            ],
        );

        // Every Filament resource's canViewAny() checks a Spatie permission
        // (see App\Filament\Concerns\GatedByPermission) — user_type alone
        // only grants access to the panel itself, not to what's inside it.
        // Re-running this seeder on an admin created before this role
        // assignment existed (e.g. via a manual tinker User::create()) will
        // backfill it, which is exactly what fixes an admin who can log in
        // but sees nothing but the Dashboard.
        if (! $admin->hasRole('Super Admin', 'admin')) {
            $admin->assignRole(Role::findOrCreate('Super Admin', 'admin'));
        }
    }
}
