<?php

use App\Actions\Vendor\ApproveVendorApplicationAction;
use App\Actions\Vendor\RejectVendorApplicationAction;
use App\Enums\UserType;
use App\Enums\VendorApplicationStatus;
use App\Exceptions\VendorApplicationConflictException;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use App\Models\VendorDocument;
use App\Notifications\VendorApplicationApprovedNotification;
use App\Notifications\VendorApplicationRejectedNotification;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\VendorSubscriptionPlanSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    (new VendorSubscriptionPlanSeeder)->run();
});

test('approving an application provisions user, vendor, store, subscription, and role', function () {
    Notification::fake();

    $application = VendorApplication::factory()->create();
    VendorDocument::factory()->forApplication($application)->create();

    $admin = User::factory()->admin()->create();

    $vendor = app(ApproveVendorApplicationAction::class)->handle($application, $admin);

    expect($vendor->business_name)->toBe($application->business_name)
        ->and($vendor->user->user_type)->toBe(UserType::VendorOwner)
        ->and($vendor->user->email_verified_at)->not->toBeNull()
        ->and($vendor->store)->not->toBeNull()
        ->and($vendor->store->slug)->toBe($application->store_slug)
        ->and($vendor->user->hasRole('Vendor Owner'))->toBeTrue()
        ->and($vendor->currentSubscription())->not->toBeNull();

    $application->refresh();
    expect($application->status)->toBe(VendorApplicationStatus::Approved)
        ->and($application->vendor_id)->toBe($vendor->id)
        ->and($application->reviewed_by)->toBe($admin->id);

    $document = VendorDocument::first();
    expect($document->vendor_id)->toBe($vendor->id)
        ->and($document->vendor_application_id)->toBeNull();

    Notification::assertSentTo($vendor->user, VendorApplicationApprovedNotification::class);
});

test('approving an application whose store slug collides gets a unique slug', function () {
    Notification::fake();

    $existingVendor = Vendor::factory()->create();
    Store::factory()->for($existingVendor)->create(['slug' => 'taken-slug']);

    $application = VendorApplication::factory()->create(['store_slug' => 'taken-slug']);

    $vendor = app(ApproveVendorApplicationAction::class)->handle($application);

    expect($vendor->store->slug)->not->toBe('taken-slug')
        ->and($vendor->store->slug)->toStartWith('taken-slug-');
});

test('approving an application whose phone number is already taken raises a clear error instead of crashing', function () {
    User::factory()->create(['phone' => '08072750486']);
    $application = VendorApplication::factory()->create(['phone' => '08072750486']);

    expect(fn () => app(ApproveVendorApplicationAction::class)->handle($application))
        ->toThrow(VendorApplicationConflictException::class, 'phone number "08072750486" is already used by another account');

    $application->refresh();
    expect($application->status)->toBe(VendorApplicationStatus::Pending)
        ->and(User::where('email', $application->email)->exists())->toBeFalse();
});

test('approving an application whose email is already taken raises a clear error instead of crashing', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $application = VendorApplication::factory()->create(['email' => 'taken@example.com', 'phone' => null]);

    expect(fn () => app(ApproveVendorApplicationAction::class)->handle($application))
        ->toThrow(VendorApplicationConflictException::class, 'email "taken@example.com" is already used by another account');

    $application->refresh();
    expect($application->status)->toBe(VendorApplicationStatus::Pending);
});

test('rejecting an application records the reason and notifies the applicant', function () {
    Notification::fake();

    $application = VendorApplication::factory()->create();
    $admin = User::factory()->admin()->create();

    app(RejectVendorApplicationAction::class)->handle($application, 'Missing documents', $admin);

    $application->refresh();
    expect($application->status)->toBe(VendorApplicationStatus::Rejected)
        ->and($application->rejection_reason)->toBe('Missing documents')
        ->and($application->reviewed_by)->toBe($admin->id);

    Notification::assertSentOnDemand(VendorApplicationRejectedNotification::class);
});
