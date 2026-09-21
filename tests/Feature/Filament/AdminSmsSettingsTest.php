<?php

use App\Filament\Pages\SmsSettings;
use App\Models\SmsSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function smsSettingsAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load and save sms settings', function () {
    $admin = smsSettingsAdmin();

    $this->actingAs($admin, 'admin')->get('/admin/sms-settings')->assertOk();

    Livewire::actingAs($admin, 'admin')
        ->test(SmsSettings::class)
        ->fillForm([
            'is_active' => true,
            'sandbox' => false,
            'username' => 'my-at-username',
            'api_key' => 'a-real-secret',
            'sender_id' => 'ONEMARKET',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = SmsSetting::current();
    expect($settings->is_active)->toBeTrue()
        ->and($settings->sandbox)->toBeFalse()
        ->and($settings->username)->toBe('my-at-username')
        ->and($settings->api_key)->toBe('a-real-secret')
        ->and($settings->sender_id)->toBe('ONEMARKET');
});

test('leaving the API key blank on save keeps the existing key', function () {
    $admin = smsSettingsAdmin();
    SmsSetting::current()->update(['api_key' => 'original-secret', 'username' => 'my-username']);

    Livewire::actingAs($admin, 'admin')
        ->test(SmsSettings::class)
        ->fillForm(['username' => 'my-username', 'is_active' => true])
        ->call('save');

    expect(SmsSetting::current()->api_key)->toBe('original-secret');
});

test('an admin without notifications.manage cannot access sms settings', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/sms-settings')->assertForbidden();
});

test('sending a test sms reports success when Africa\'s Talking accepts it', function () {
    Http::fake(['api.sandbox.africastalking.com/*' => Http::response(['SMSMessageData' => []], 200)]);
    $admin = smsSettingsAdmin();
    SmsSetting::current()->update(['is_active' => true, 'sandbox' => true, 'username' => 'sandbox', 'api_key' => 'test-key']);

    Livewire::actingAs($admin, 'admin')
        ->test(SmsSettings::class)
        ->callAction('sendTestSms', data: ['phone' => '+15551234567'])
        ->assertNotified('Test SMS sent');
});

test('sending a test sms reports failure instead of crashing when the gateway rejects it', function () {
    Http::fake(['api.sandbox.africastalking.com/*' => Http::response(['error' => 'InvalidCredentials'], 401)]);
    $admin = smsSettingsAdmin();
    SmsSetting::current()->update(['is_active' => true, 'sandbox' => true, 'username' => 'sandbox', 'api_key' => 'bad-key']);

    Livewire::actingAs($admin, 'admin')
        ->test(SmsSettings::class)
        ->callAction('sendTestSms', data: ['phone' => '+15551234567'])
        ->assertNotified('Could not send the test SMS');
});
