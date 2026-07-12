<?php

use App\Enums\VendorStatus;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function superAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('approving a pending vendor sets status and approved_at', function () {
    $admin = superAdmin();
    $vendor = Vendor::factory()->pending()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendors::class)
        ->callTableAction('approve', $vendor);

    $vendor->refresh();
    expect($vendor->status)->toBe(VendorStatus::Approved)
        ->and($vendor->approved_at)->not->toBeNull();
});

test('rejecting a pending vendor requires a reason and sets status', function () {
    $admin = superAdmin();
    $vendor = Vendor::factory()->pending()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendors::class)
        ->callTableAction('reject', $vendor, data: ['rejection_reason' => 'Incomplete documents']);

    $vendor->refresh();
    expect($vendor->status)->toBe(VendorStatus::Rejected)
        ->and($vendor->rejection_reason)->toBe('Incomplete documents');
});

test('suspending an approved vendor sets status and suspended_at', function () {
    $admin = superAdmin();
    $vendor = Vendor::factory()->create(['status' => VendorStatus::Approved]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendors::class)
        ->callTableAction('suspend', $vendor, data: ['rejection_reason' => 'Policy violation']);

    $vendor->refresh();
    expect($vendor->status)->toBe(VendorStatus::Suspended)
        ->and($vendor->suspended_at)->not->toBeNull();
});

test('reactivating a suspended vendor restores approved status', function () {
    $admin = superAdmin();
    $vendor = Vendor::factory()->suspended()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendors::class)
        ->callTableAction('reactivate', $vendor);

    $vendor->refresh();
    expect($vendor->status)->toBe(VendorStatus::Approved)
        ->and($vendor->suspended_at)->toBeNull();
});

test('terminating a vendor deactivates it', function () {
    $admin = superAdmin();
    $vendor = Vendor::factory()->create(['status' => VendorStatus::Approved]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendors::class)
        ->callTableAction('terminate', $vendor);

    $vendor->refresh();
    expect($vendor->status)->toBe(VendorStatus::Deactivated);
});

test('the approve action is hidden for a user without vendors.approve permission', function () {
    $staff = User::factory()->admin()->create();
    $staff->givePermissionTo(Permission::where('name', 'vendors.view')->where('guard_name', 'admin')->first());
    $vendor = Vendor::factory()->pending()->create();

    Livewire::actingAs($staff, 'admin')
        ->test(ListVendors::class)
        ->assertTableActionHidden('approve', $vendor);
});

test('the approve action is hidden for an already approved vendor', function () {
    $admin = superAdmin();
    $vendor = Vendor::factory()->create(['status' => VendorStatus::Approved]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendors::class)
        ->assertTableActionHidden('approve', $vendor);
});
