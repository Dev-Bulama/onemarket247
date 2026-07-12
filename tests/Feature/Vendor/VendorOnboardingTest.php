<?php

use App\Enums\VendorApplicationStatus;
use App\Models\Setting;
use App\Models\User;
use App\Models\VendorApplication;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\VendorSubscriptionPlanSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Storage::fake('local');
    (new SettingsSeeder)->run();
    (new RolePermissionSeeder)->run();
    (new VendorSubscriptionPlanSeeder)->run();
});

function submitVendorApplication(array $overrides = []): TestResponse
{
    $data = array_merge([
        'full_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '+15551234567',
        'business_name' => 'Jane Co',
        'store_name' => 'Jane Store',
        'store_description' => 'We sell things',
        'bank_name' => 'First Bank',
        'bank_account_name' => 'Jane Doe',
        'bank_account_number' => '1234567890',
        'identity_document' => UploadedFile::fake()->create('id.pdf', 100, 'application/pdf'),
        'business_registration_document' => UploadedFile::fake()->create('reg.pdf', 100, 'application/pdf'),
        'terms' => '1',
    ], $overrides);

    return test()->post('/vendor/register', $data);
}

test('a complete application is accepted and stored as pending under manual approval', function () {
    $response = submitVendorApplication();

    $response->assertRedirect(route('vendor.register.submitted'));

    $application = VendorApplication::where('email', 'jane@example.com')->firstOrFail();
    expect($application->status)->toBe(VendorApplicationStatus::Pending)
        ->and($application->documents()->count())->toBe(2)
        ->and($application->documents()->pluck('vendor_id')->filter()->isEmpty())->toBeTrue();
});

test('automatic approval mode provisions the vendor immediately', function () {
    Setting::where('key', 'vendor.approval_mode')->update(['value' => 'automatic']);

    submitVendorApplication(['email' => 'auto@example.com']);

    $application = VendorApplication::where('email', 'auto@example.com')->firstOrFail();
    expect($application->status)->toBe(VendorApplicationStatus::Approved)
        ->and($application->vendor_id)->not->toBeNull()
        ->and($application->vendor->store)->not->toBeNull()
        ->and($application->vendor->user->email)->toBe('auto@example.com');
});

test('an application without accepting terms is rejected with a validation error', function () {
    $response = submitVendorApplication(['terms' => null]);

    $response->assertSessionHasErrors('terms');
    expect(VendorApplication::where('email', 'jane@example.com')->exists())->toBeFalse();
});

test('an application with an email already used by an existing account is rejected', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = submitVendorApplication(['email' => 'taken@example.com']);

    $response->assertSessionHasErrors('email');
});

test('required documents must be uploaded', function () {
    $response = test()->post('/vendor/register', [
        'full_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'business_name' => 'Jane Co',
        'store_name' => 'Jane Store',
        'bank_name' => 'First Bank',
        'bank_account_name' => 'Jane Doe',
        'bank_account_number' => '1234567890',
        'terms' => '1',
    ]);

    $response->assertSessionHasErrors(['identity_document', 'business_registration_document']);
});
