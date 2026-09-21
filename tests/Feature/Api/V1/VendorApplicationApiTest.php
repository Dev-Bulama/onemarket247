<?php

use App\Enums\VendorApplicationStatus;
use App\Models\Setting;
use App\Models\SmsSetting;
use App\Models\User;
use App\Models\VendorApplication;
use App\Notifications\NewVendorApplicationNotification;
use App\Notifications\VendorApplicationReceivedNotification;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Database\Seeders\VendorSubscriptionPlanSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Storage::fake('local');
    (new SettingsSeeder)->run();
    (new RolePermissionSeeder)->run();
    (new VendorSubscriptionPlanSeeder)->run();
});

function submitVendorApplicationApi(array $overrides = []): TestResponse
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

    return test()->postJson('/api/v1/vendor/apply', $data);
}

test('a complete application is accepted and stored as pending under manual approval', function () {
    $response = submitVendorApplicationApi();

    $response->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    $application = VendorApplication::where('email', 'jane@example.com')->firstOrFail();
    expect($application->status)->toBe(VendorApplicationStatus::Pending)
        ->and($application->documents()->count())->toBe(2)
        ->and($response->json('message'))->toContain('pending review');
});

test('automatic approval mode provisions the vendor immediately and tells the applicant they can log in', function () {
    Setting::where('key', 'vendor.approval_mode')->update(['value' => 'automatic']);

    $response = submitVendorApplicationApi(['email' => 'auto@example.com']);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'approved');
    expect($response->json('message'))->toContain('log in');

    $application = VendorApplication::where('email', 'auto@example.com')->firstOrFail();
    expect($application->status)->toBe(VendorApplicationStatus::Approved)
        ->and($application->vendor_id)->not->toBeNull()
        ->and($application->vendor->store)->not->toBeNull();
});

test('submitting an application under manual approval sends a "we received it" email', function () {
    Notification::fake();

    submitVendorApplicationApi();

    Notification::assertSentOnDemand(VendorApplicationReceivedNotification::class);
});

test('an auto-approved application does not also get the "we received it" email — the approval email covers it', function () {
    Setting::where('key', 'vendor.approval_mode')->update(['value' => 'automatic']);
    Notification::fake();

    submitVendorApplicationApi(['email' => 'auto2@example.com']);

    Notification::assertSentOnDemandTimes(VendorApplicationReceivedNotification::class, 0);
});

test('a new application always notifies the platform inbox, regardless of approval mode', function () {
    Notification::fake();

    submitVendorApplicationApi();

    Notification::assertSentOnDemand(
        NewVendorApplicationNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === config('mail.from.address'),
    );
});

test('a submitted application is assigned a unique reference number', function () {
    submitVendorApplicationApi();

    $application = VendorApplication::where('email', 'jane@example.com')->firstOrFail();
    expect($application->reference_number)->not->toBeNull()
        ->and($application->reference_number)->toStartWith('VA-'.now()->year.'-');
});

test('an active SMS gateway sends the applicant a confirmation text', function () {
    Http::fake(['api.sandbox.africastalking.com/*' => Http::response(['SMSMessageData' => []])]);
    SmsSetting::current()->update(['is_active' => true, 'sandbox' => true, 'username' => 'sandbox', 'api_key' => 'test-key']);

    submitVendorApplicationApi();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'africastalking.com')
        && $request['to'] === '+15551234567');
});

test('an inactive SMS gateway never attempts to send', function () {
    Http::fake();

    submitVendorApplicationApi();

    Http::assertNothingSent();
});

test('an application without accepting terms is rejected with a validation error', function () {
    $response = submitVendorApplicationApi(['terms' => null]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('terms');
    expect(VendorApplication::where('email', 'jane@example.com')->exists())->toBeFalse();
});

test('an application with an email already used by an existing account is rejected', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = submitVendorApplicationApi(['email' => 'taken@example.com']);

    $response->assertStatus(422)->assertJsonValidationErrors('email');
});

test('required documents must be uploaded', function () {
    $response = test()->postJson('/api/v1/vendor/apply', [
        'full_name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'business_name' => 'Jane Co',
        'store_name' => 'Jane Store',
        'bank_name' => 'First Bank',
        'bank_account_name' => 'Jane Doe',
        'bank_account_number' => '1234567890',
        'terms' => '1',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['identity_document', 'business_registration_document']);
});
