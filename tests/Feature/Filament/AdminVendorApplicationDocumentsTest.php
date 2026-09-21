<?php

use App\Enums\VendorDocumentStatus;
use App\Filament\RelationManagers\VendorDocumentsRelationManager;
use App\Filament\Resources\VendorApplications\Pages\ViewVendorApplication;
use App\Filament\Resources\Vendors\Pages\EditVendor;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use App\Models\VendorDocument;
use App\Notifications\VendorDocumentStatusChangedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function vendorDocsAdmin(): User
{
    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $admin;
}

test('an admin can see a link to open each vendor onboarding document', function () {
    $admin = vendorDocsAdmin();

    $application = VendorApplication::factory()->create();
    $document = VendorDocument::factory()->forApplication($application)->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ViewVendorApplication::class, ['record' => $application->getKey()])
        ->assertSee('Open document')
        ->assertSee(route('vendor-documents.download', $document), false);
});

test('an admin can approve an onboarding document, notifying the applicant by email', function () {
    Notification::fake();
    $admin = vendorDocsAdmin();

    $application = VendorApplication::factory()->create();
    $document = VendorDocument::factory()->forApplication($application)->create(['status' => VendorDocumentStatus::Pending]);

    Livewire::actingAs($admin, 'admin')
        ->test(VendorDocumentsRelationManager::class, [
            'ownerRecord' => $application,
            'pageClass' => ViewVendorApplication::class,
        ])
        ->callTableAction('approve', $document);

    $document->refresh();
    expect($document->status)->toBe(VendorDocumentStatus::Verified)
        ->and($document->verified_by)->toBe($admin->id)
        ->and($document->verified_at)->not->toBeNull();

    expect(AuditLog::where('action', 'vendor_document.approved')->where('auditable_id', $document->id)->exists())->toBeTrue();

    Notification::assertSentOnDemand(
        VendorDocumentStatusChangedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $application->email,
    );
});

test('an admin can reject an onboarding document with a reason', function () {
    Notification::fake();
    $admin = vendorDocsAdmin();

    $application = VendorApplication::factory()->create();
    $document = VendorDocument::factory()->forApplication($application)->create(['status' => VendorDocumentStatus::Pending]);

    Livewire::actingAs($admin, 'admin')
        ->test(VendorDocumentsRelationManager::class, [
            'ownerRecord' => $application,
            'pageClass' => ViewVendorApplication::class,
        ])
        ->callTableAction('reject', $document, data: ['reason' => 'Blurry ID photo, please resubmit a clearer copy.']);

    $document->refresh();
    expect($document->status)->toBe(VendorDocumentStatus::Rejected)
        ->and($document->rejection_reason)->toBe('Blurry ID photo, please resubmit a clearer copy.')
        ->and($document->verified_by)->toBe($admin->id);

    expect(AuditLog::where('action', 'vendor_document.rejected')->where('auditable_id', $document->id)->exists())->toBeTrue();
});

test('an admin can approve a post-approval vendor document, notifying the vendor\'s own account', function () {
    Notification::fake();
    $admin = vendorDocsAdmin();

    $vendor = Vendor::factory()->create();
    $document = VendorDocument::factory()->create(['vendor_id' => $vendor->id, 'status' => VendorDocumentStatus::Pending]);

    Livewire::actingAs($admin, 'admin')
        ->test(VendorDocumentsRelationManager::class, [
            'ownerRecord' => $vendor,
            'pageClass' => EditVendor::class,
        ])
        ->callTableAction('approve', $document);

    expect($document->fresh()->status)->toBe(VendorDocumentStatus::Verified);

    Notification::assertSentTo($vendor->user, VendorDocumentStatusChangedNotification::class);
});

test('a staff admin without vendors.approve cannot see the approve/reject document actions', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $application = VendorApplication::factory()->create();
    $document = VendorDocument::factory()->forApplication($application)->create();

    Livewire::actingAs($staff, 'admin')
        ->test(VendorDocumentsRelationManager::class, [
            'ownerRecord' => $application,
            'pageClass' => ViewVendorApplication::class,
        ])
        ->assertTableActionHidden('approve', $document)
        ->assertTableActionHidden('reject', $document);
});
