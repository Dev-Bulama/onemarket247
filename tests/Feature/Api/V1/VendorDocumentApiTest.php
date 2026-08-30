<?php

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('a vendor can upload a new document and list only their own documents', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $otherVendor = Vendor::factory()->create();
    VendorDocument::factory()->create(['vendor_id' => $otherVendor->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/documents', [
            'type' => 'tax_certificate',
            'file' => UploadedFile::fake()->create('tax.pdf', 100, 'application/pdf'),
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'tax_certificate')
        ->assertJsonPath('data.status', 'pending');

    expect(VendorDocument::where('vendor_id', $vendor->id)->count())->toBe(1);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/documents')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'tax_certificate');
});

test('uploading a document validates the type and file', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/documents', ['type' => 'not-a-real-type'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'file']);
});

test('a store staff member cannot upload a vendor document', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create(['user_type' => UserType::VendorStaff]);
    StoreStaff::factory()->create(['store_id' => $store->id, 'user_id' => $staffUser->id, 'status' => StoreStaffStatus::Active]);
    $token = $staffUser->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/documents', [
            'type' => 'identity',
            'file' => UploadedFile::fake()->create('id.pdf', 50, 'application/pdf'),
        ])
        ->assertForbidden();
});
