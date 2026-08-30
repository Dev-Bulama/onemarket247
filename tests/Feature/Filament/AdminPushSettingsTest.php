<?php

use App\Filament\Pages\PushSettings;
use App\Models\DeviceToken;
use App\Models\PushSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function pushSettingsAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load and save push settings', function () {
    $admin = pushSettingsAdmin();

    $this->actingAs($admin, 'admin')->get('/admin/push-settings')->assertOk();

    Livewire::actingAs($admin, 'admin')
        ->test(PushSettings::class)
        ->fillForm([
            'is_active' => true,
            'app_id' => 'my-onesignal-app-id',
            'rest_api_key' => 'a-real-secret',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = PushSetting::current();
    expect($settings->is_active)->toBeTrue()
        ->and($settings->app_id)->toBe('my-onesignal-app-id')
        ->and($settings->rest_api_key)->toBe('a-real-secret');
});

test('leaving the REST API key blank on save keeps the existing key', function () {
    $admin = pushSettingsAdmin();
    PushSetting::current()->update(['rest_api_key' => 'original-secret', 'app_id' => 'my-app-id']);

    Livewire::actingAs($admin, 'admin')
        ->test(PushSettings::class)
        ->fillForm(['app_id' => 'my-app-id', 'is_active' => true])
        ->call('save');

    expect(PushSetting::current()->rest_api_key)->toBe('original-secret');
});

test('an admin without notifications.manage cannot access push settings', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/push-settings')->assertForbidden();
});

test('sending a test push reports success when OneSignal accepts it', function () {
    Http::fake(['onesignal.com/*' => Http::response(['id' => 'abc'], 200)]);
    $admin = pushSettingsAdmin();
    PushSetting::current()->update(['is_active' => true, 'app_id' => 'app-id', 'rest_api_key' => 'rest-key']);
    $user = User::factory()->create();
    DeviceToken::create(['user_id' => $user->id, 'token' => 'test-device']);

    Livewire::actingAs($admin, 'admin')
        ->test(PushSettings::class)
        ->callAction('sendTestPush', data: ['device_token' => 'test-device'])
        ->assertNotified('Test push sent');
});

test('sending a test push reports failure instead of crashing when OneSignal rejects it', function () {
    Http::fake(['onesignal.com/*' => Http::response(['errors' => ['Invalid app_id']], 400)]);
    $admin = pushSettingsAdmin();
    PushSetting::current()->update(['is_active' => true, 'app_id' => 'bad-app-id', 'rest_api_key' => 'rest-key']);
    $user = User::factory()->create();
    DeviceToken::create(['user_id' => $user->id, 'token' => 'test-device']);

    Livewire::actingAs($admin, 'admin')
        ->test(PushSettings::class)
        ->callAction('sendTestPush', data: ['device_token' => 'test-device'])
        ->assertNotified('Could not send the test push');
});

test('sending a test push to an unregistered device token reports an error', function () {
    $admin = pushSettingsAdmin();
    PushSetting::current()->update(['is_active' => true, 'app_id' => 'app-id', 'rest_api_key' => 'rest-key']);

    Livewire::actingAs($admin, 'admin')
        ->test(PushSettings::class)
        ->callAction('sendTestPush', data: ['device_token' => 'unknown-device'])
        ->assertNotified('No device is registered with that token');
});
