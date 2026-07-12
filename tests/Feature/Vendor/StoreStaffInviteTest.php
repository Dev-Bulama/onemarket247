<?php

use App\Actions\Vendor\InviteStoreStaffAction;
use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

test('inviting a new email creates a vendor staff user and links them to the store', function () {
    Password::shouldReceive('broker')->andReturnSelf();
    Password::shouldReceive('sendResetLink')->once();

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();

    $staff = app(InviteStoreStaffAction::class)->handle(
        $store,
        'New Staffer',
        'staffer@example.com',
        ['store.orders.manage'],
    );

    expect($staff->status)->toBe(StoreStaffStatus::Invited)
        ->and($staff->user->user_type)->toBe(UserType::VendorStaff)
        ->and($staff->user->email)->toBe('staffer@example.com')
        ->and($staff->user->hasPermissionTo('store.orders.manage', 'vendor'))->toBeTrue();
});

test('inviting an existing email reuses that user instead of creating a duplicate', function () {
    Password::shouldReceive('broker')->andReturnSelf();
    Password::shouldReceive('sendResetLink')->once();

    $existing = User::factory()->create(['email' => 'staffer@example.com']);
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();

    $staff = app(InviteStoreStaffAction::class)->handle($store, 'Ignored Name', 'staffer@example.com', []);

    expect($staff->user_id)->toBe($existing->id)
        ->and(User::where('email', 'staffer@example.com')->count())->toBe(1);
});

test('a staff members first vendor login activates their invitation', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();

    $staffUser = User::factory()->create(['user_type' => UserType::VendorStaff]);
    $staffLink = StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Invited,
        'joined_at' => null,
    ]);

    event(new Login('vendor', $staffUser, false));

    $staffLink->refresh();
    expect($staffLink->status)->toBe(StoreStaffStatus::Active)
        ->and($staffLink->joined_at)->not->toBeNull();
});

test('a staff member cannot manage staff at all, even with full store permissions', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();

    $staffUser = User::factory()->create(['user_type' => UserType::VendorStaff]);
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);
    $staffUser->givePermissionTo(
        Permission::where('name', 'store.staff.manage')->where('guard_name', 'vendor')->first()
    );

    $otherLink = StoreStaff::factory()->create(['store_id' => $store->id]);

    expect($staffUser->can('viewAny', StoreStaff::class))->toBeFalse()
        ->and($staffUser->can('create', StoreStaff::class))->toBeFalse()
        ->and($staffUser->can('update', $otherLink))->toBeFalse()
        ->and($staffUser->can('delete', $otherLink))->toBeFalse();
});

test('the store owner can manage staff regardless of permission grants', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $staffLink = StoreStaff::factory()->create(['store_id' => $store->id]);

    expect($vendor->user->can('viewAny', StoreStaff::class))->toBeTrue()
        ->and($vendor->user->can('create', StoreStaff::class))->toBeTrue()
        ->and($vendor->user->can('update', $staffLink))->toBeTrue()
        ->and($vendor->user->can('delete', $staffLink))->toBeTrue();
});
