<?php

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

test('an owner can invite, list, update and remove store staff', function () {
    Password::shouldReceive('broker')->andReturnSelf();
    Password::shouldReceive('sendResetLink')->once();

    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $invite = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/staff', [
            'name' => 'New Staffer',
            'email' => 'staffer@example.com',
            'permissions' => ['store.orders.manage'],
        ]);

    $invite->assertCreated()
        ->assertJsonPath('data.email', 'staffer@example.com')
        ->assertJsonPath('data.status', 'invited')
        ->assertJsonPath('data.permissions', ['store.orders.manage']);

    $staffId = $invite->json('data.id');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/staff')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/vendor/staff/{$staffId}", [
            'status' => 'suspended',
            'permissions' => ['store.orders.manage', 'store.products.manage'],
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'suspended')
        ->assertJsonPath('data.permissions', ['store.orders.manage', 'store.products.manage']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/vendor/staff/{$staffId}")
        ->assertOk();

    expect(StoreStaff::find($staffId))->toBeNull();
});

test('a staff member cannot invite, view, update or remove staff even with store.staff.manage', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create(['user_type' => UserType::VendorStaff]);
    StoreStaff::factory()->create(['store_id' => $store->id, 'user_id' => $staffUser->id, 'status' => StoreStaffStatus::Active]);
    $staffUser->givePermissionTo(
        Permission::where('name', 'store.staff.manage')->where('guard_name', 'vendor')->first()
    );
    $token = $staffUser->createToken('t', ['vendor:*'])->plainTextToken;

    $otherStaffLink = StoreStaff::factory()->create(['store_id' => $store->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/staff')
        ->assertForbidden();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/staff', ['name' => 'X', 'email' => 'x@example.com'])
        ->assertForbidden();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/vendor/staff/{$otherStaffLink->id}", ['status' => 'suspended'])
        ->assertForbidden();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/vendor/staff/{$otherStaffLink->id}")
        ->assertForbidden();
});

test('an owner cannot manage another vendor\'s staff', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $otherVendor = Vendor::factory()->create();
    $otherStore = Store::factory()->create(['vendor_id' => $otherVendor->id]);
    $otherStaffLink = StoreStaff::factory()->create(['store_id' => $otherStore->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/vendor/staff/{$otherStaffLink->id}", ['status' => 'suspended'])
        ->assertForbidden();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/vendor/staff/{$otherStaffLink->id}")
        ->assertForbidden();
});

test('inviting staff validates permission names and email format', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/staff', [
            'name' => 'Bad Perm',
            'email' => 'not-an-email',
            'permissions' => ['not.a.real.permission'],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'permissions.0']);
});

test('updating staff status can never be set to invited', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $staffLink = StoreStaff::factory()->create(['store_id' => $store->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/vendor/staff/{$staffLink->id}", ['status' => 'invited'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});
