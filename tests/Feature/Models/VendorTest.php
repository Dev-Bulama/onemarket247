<?php

use App\Enums\VendorStatus;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;

test('a vendor belongs to a user and can have one store', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);

    expect($vendor->user)->not->toBeNull()
        ->and($vendor->store->id)->toBe($store->id)
        ->and($store->vendor->id)->toBe($vendor->id);
});

test('vendor status casts to the VendorStatus enum', function () {
    $vendor = Vendor::factory()->create(['status' => VendorStatus::Pending]);

    expect($vendor->fresh()->status)->toBe(VendorStatus::Pending);
});

test('only an approved vendor may access the dashboard', function () {
    $approved = Vendor::factory()->create(['status' => VendorStatus::Approved]);
    $suspended = Vendor::factory()->suspended()->create();

    expect($approved->canAccessDashboard())->toBeTrue()
        ->and($suspended->canAccessDashboard())->toBeFalse();
});

test('bank account fields are stored encrypted and decrypt transparently', function () {
    $vendor = Vendor::factory()->create(['bank_account_number' => '0123456789']);

    $raw = DB::table('vendors')->where('id', $vendor->id)->value('bank_account_number');

    expect($raw)->not->toBe('0123456789')
        ->and($vendor->fresh()->bank_account_number)->toBe('0123456789');
});

test('soft-deleting a vendor leaves its owning user account intact', function () {
    $vendor = Vendor::factory()->create();
    $userId = $vendor->user_id;

    $vendor->delete();

    expect(Vendor::withTrashed()->find($vendor->id))->not->toBeNull()
        ->and(User::find($userId))->not->toBeNull();
});

test('force-deleting a vendor also deletes its owning user account, freeing the email', function () {
    $vendor = Vendor::factory()->create();
    $userId = $vendor->user_id;
    $email = $vendor->user->email;

    $vendor->forceDelete();

    expect(Vendor::withTrashed()->find($vendor->id))->toBeNull()
        ->and(User::find($userId))->toBeNull()
        ->and(User::where('email', $email)->exists())->toBeFalse();
});
