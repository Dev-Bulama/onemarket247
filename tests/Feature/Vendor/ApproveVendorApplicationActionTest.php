<?php

use App\Actions\Vendor\ApproveVendorApplicationAction;
use App\Actions\Vendor\RejectVendorApplicationAction;
use App\Enums\UserType;
use App\Enums\VendorApplicationStatus;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use App\Models\VendorDocument;
use App\Notifications\VendorApplicationRejectedNotification;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\VendorSubscriptionPlanSeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    (new VendorSubscriptionPlanSeeder)->run();
});

test('approving an application provisions user, vendor, store, subscription, and role', function () {
    Password::shouldReceive('broker')->andReturnSelf();
    Password::shouldReceive('sendResetLink')->once();

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
    expect($document->vendor_id)->toBe($vendor->id);
});

test('approving an application whose store slug collides gets a unique slug', function () {
    Password::shouldReceive('broker')->andReturnSelf();
    Password::shouldReceive('sendResetLink')->once();

    $existingVendor = Vendor::factory()->create();
    Store::factory()->for($existingVendor)->create(['slug' => 'taken-slug']);

    $application = VendorApplication::factory()->create(['store_slug' => 'taken-slug']);

    $vendor = app(ApproveVendorApplicationAction::class)->handle($application);

    expect($vendor->store->slug)->not->toBe('taken-slug')
        ->and($vendor->store->slug)->toStartWith('taken-slug-');
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
