<?php

use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Spatie\Permission\Models\Permission;

test('a vendor owner can view their own document', function () {
    $vendor = Vendor::factory()->create();
    $document = VendorDocument::factory()->create(['vendor_id' => $vendor->id]);

    expect($vendor->user->can('view', $document))->toBeTrue()
        ->and($vendor->user->can('viewAny', VendorDocument::class))->toBeTrue()
        ->and($vendor->user->can('create', VendorDocument::class))->toBeTrue();
});

test('an unrelated vendor cannot view someone elses document', function () {
    $vendor = Vendor::factory()->create();
    $document = VendorDocument::factory()->create(['vendor_id' => $vendor->id]);

    $stranger = Vendor::factory()->create();

    expect($stranger->user->can('view', $document))->toBeFalse();
});

test('a plain customer cannot view or create vendor documents', function () {
    $document = VendorDocument::factory()->create();
    $customer = User::factory()->create();

    expect($customer->can('view', $document))->toBeFalse()
        ->and($customer->can('viewAny', VendorDocument::class))->toBeFalse()
        ->and($customer->can('create', VendorDocument::class))->toBeFalse();
});

test('an admin with vendors.view can view any document but only vendors.approve can verify it', function () {
    Permission::findOrCreate('vendors.view', 'web');
    Permission::findOrCreate('vendors.approve', 'web');

    $document = VendorDocument::factory()->create();

    $viewer = User::factory()->admin()->create();
    $viewer->givePermissionTo('vendors.view');

    $approver = User::factory()->admin()->create();
    $approver->givePermissionTo('vendors.approve');

    expect($viewer->can('view', $document))->toBeTrue()
        ->and($viewer->can('update', $document))->toBeFalse()
        ->and($approver->can('update', $document))->toBeTrue();
});
