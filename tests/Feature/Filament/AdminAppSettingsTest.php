<?php

use App\Filament\Pages\AppSettings;
use App\Models\AppSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function appSettingsAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load and save app settings', function () {
    $admin = appSettingsAdmin();

    $this->actingAs($admin, 'admin')->get('/admin/app-settings')->assertOk();

    Livewire::actingAs($admin, 'admin')
        ->test(AppSettings::class)
        ->fillForm([
            'active_environment' => 'local',
            'force_production' => false,
            'production_api_url' => 'https://onemarket247.com/api/v1',
            'local_api_url' => 'http://192.168.1.50:8000/api/v1',
            'app_name' => 'OneMarket 24/7',
            'logo_url' => 'https://onemarket247.com/logo.png',
            'splash_logo_url' => 'https://onemarket247.com/splash.png',
            'min_app_version' => '1.0.0',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = AppSetting::current();
    expect($settings->active_environment->value)->toBe('local')
        ->and($settings->force_production)->toBeFalse()
        ->and($settings->local_api_url)->toBe('http://192.168.1.50:8000/api/v1')
        ->and($settings->app_name)->toBe('OneMarket 24/7');
});

test('an admin without settings.manage cannot access app settings', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/app-settings')->assertForbidden();
});
