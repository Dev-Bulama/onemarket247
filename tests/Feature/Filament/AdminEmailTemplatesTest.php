<?php

use App\Filament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Models\EmailTemplate;
use App\Models\User;
use App\Support\Mail\EmailTemplateKeys;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    (new EmailTemplateSeeder)->run();
});

function emailTemplateAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('the seeder creates one template per key, all inactive by default', function () {
    expect(EmailTemplate::count())->toBe(5)
        ->and(EmailTemplate::where('is_active', true)->count())->toBe(0)
        ->and(EmailTemplate::where('key', EmailTemplateKeys::CustomerWelcome)->exists())->toBeTrue()
        ->and(EmailTemplate::where('key', EmailTemplateKeys::OrderConfirmation)->exists())->toBeTrue()
        ->and(EmailTemplate::where('key', EmailTemplateKeys::VendorApplicationApproved)->exists())->toBeTrue()
        ->and(EmailTemplate::where('key', EmailTemplateKeys::VendorApplicationRejected)->exists())->toBeTrue()
        ->and(EmailTemplate::where('key', EmailTemplateKeys::MarketingSample)->exists())->toBeTrue();
});

test('re-running the seeder does not overwrite an admin-edited template', function () {
    EmailTemplate::where('key', EmailTemplateKeys::CustomerWelcome)->update(['subject' => 'Custom subject', 'is_active' => true]);

    (new EmailTemplateSeeder)->run();

    $template = EmailTemplate::where('key', EmailTemplateKeys::CustomerWelcome)->first();
    expect($template->subject)->toBe('Custom subject')
        ->and($template->is_active)->toBeTrue();
});

test('an admin can edit and activate an email template', function () {
    $admin = emailTemplateAdmin();
    $template = EmailTemplate::where('key', EmailTemplateKeys::CustomerWelcome)->first();

    $this->actingAs($admin, 'admin')->get("/admin/email-templates/{$template->id}/edit")->assertOk();

    Livewire::actingAs($admin, 'admin')
        ->test(EditEmailTemplate::class, ['record' => $template->id])
        ->fillForm([
            'subject' => 'Hey {{customer_name}}, welcome aboard!',
            'body' => 'Custom welcome body for {{customer_name}}.',
            'is_active' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $template->refresh();
    expect($template->subject)->toBe('Hey {{customer_name}}, welcome aboard!')
        ->and($template->is_active)->toBeTrue();
});

test('an admin without email_templates.manage cannot access email templates', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/email-templates')->assertForbidden();
});

test('render substitutes placeholders into subject and body', function () {
    $template = EmailTemplate::where('key', EmailTemplateKeys::CustomerWelcome)->first();

    $rendered = $template->render(['customer_name' => 'Ada', 'shop_url' => 'https://onemarket247.test']);

    expect($rendered['subject'])->toBe('Welcome to OneMarket247, Ada!')
        ->and($rendered['body'])->toContain('Hi Ada,')
        ->and($rendered['body'])->toContain('https://onemarket247.test');
});

test('EmailTemplate::active only returns a template that is active', function () {
    expect(EmailTemplate::active(EmailTemplateKeys::CustomerWelcome))->toBeNull();

    EmailTemplate::where('key', EmailTemplateKeys::CustomerWelcome)->update(['is_active' => true]);

    expect(EmailTemplate::active(EmailTemplateKeys::CustomerWelcome))->not->toBeNull();
});
