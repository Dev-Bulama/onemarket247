<?php

use App\Filament\Pages\MailSettings;
use App\Models\MailSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function mailSettingsAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load and save mail settings', function () {
    $admin = mailSettingsAdmin();

    $this->actingAs($admin, 'admin')->get('/admin/mail-settings')->assertOk();

    Livewire::actingAs($admin, 'admin')
        ->test(MailSettings::class)
        ->fillForm([
            'is_active' => true,
            'mailer' => 'smtp',
            'host' => 'mail.onemarket247.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'admin@onemarket247.com',
            'password' => 'a-real-secret',
            'from_address' => 'admin@onemarket247.com',
            'from_name' => 'OneMarket247',
            'primary_color' => '#FF6600',
            'footer_text' => 'OneMarket247 — everything you need, one market.',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $settings = MailSetting::current();
    expect($settings->is_active)->toBeTrue()
        ->and($settings->host)->toBe('mail.onemarket247.com')
        ->and($settings->port)->toBe(465)
        ->and($settings->password)->toBe('a-real-secret')
        ->and($settings->primary_color)->toBe('#FF6600');
});

test('leaving the password blank on save keeps the existing password', function () {
    $admin = mailSettingsAdmin();
    MailSetting::current()->update(['password' => 'original-secret', 'host' => 'mail.onemarket247.com']);

    Livewire::actingAs($admin, 'admin')
        ->test(MailSettings::class)
        ->fillForm(['host' => 'mail.onemarket247.com', 'is_active' => true])
        ->call('save');

    expect(MailSetting::current()->password)->toBe('original-secret');
});

test('an admin without smtp.manage cannot access mail settings', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/mail-settings')->assertForbidden();
});

test('sending a test email reports success when the mailer accepts it', function () {
    $admin = mailSettingsAdmin();

    Livewire::actingAs($admin, 'admin')
        ->test(MailSettings::class)
        ->call('sendTestEmail')
        ->assertNotified("Test email sent to {$admin->email}");
});

test('sending a test email reports failure instead of crashing when the mailer is unreachable', function () {
    config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);
    $admin = mailSettingsAdmin();

    Livewire::actingAs($admin, 'admin')
        ->test(MailSettings::class)
        ->call('sendTestEmail')
        ->assertNotified('Could not send the test email');
});
