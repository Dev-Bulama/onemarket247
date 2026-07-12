<?php

use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Setting;
use Database\Seeders\CountryStateCitySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('role permission seeder creates the expected admin and vendor role presets', function () {
    (new RolePermissionSeeder)->run();

    expect(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->exists())->toBeTrue()
        ->and(Role::where('name', 'Vendor Owner')->where('guard_name', 'vendor')->exists())->toBeTrue()
        ->and(Permission::where('guard_name', 'admin')->count())->toBeGreaterThan(0)
        ->and(Permission::where('guard_name', 'vendor')->count())->toBeGreaterThan(0);

    $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first();
    $allAdminPermissions = Permission::where('guard_name', 'admin')->count();

    expect($superAdmin->permissions()->count())->toBe($allAdminPermissions);
});

test('role permission seeder is idempotent', function () {
    (new RolePermissionSeeder)->run();
    $firstRunCount = Role::count();

    (new RolePermissionSeeder)->run();

    expect(Role::count())->toBe($firstRunCount);
});

test('country state city seeder creates a usable geography set', function () {
    (new CountryStateCitySeeder)->run();

    $nigeria = Country::where('iso2', 'NG')->first();

    expect($nigeria)->not->toBeNull()
        ->and($nigeria->states)->not->toBeEmpty()
        ->and($nigeria->cities)->not->toBeEmpty();
});

test('currency seeder creates exactly one default currency', function () {
    (new CurrencySeeder)->run();

    expect(Currency::where('is_default', true)->count())->toBe(1)
        ->and((float) Currency::where('code', 'USD')->first()->exchangeRate->rate)->toBe(1.0);
});

test('language seeder creates exactly one default language and at least one RTL language', function () {
    (new LanguageSeeder)->run();

    expect(Language::where('is_default', true)->count())->toBe(1)
        ->and(Language::where('direction', 'rtl')->count())->toBeGreaterThan(0);
});

test('settings seeder seeds the baseline platform settings', function () {
    (new SettingsSeeder)->run();

    expect(Setting::where('key', 'app.default_currency')->value('value'))->toBe('USD');
});
