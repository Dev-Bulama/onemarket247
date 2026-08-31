<?php

use App\Enums\VendorApplicationStatus;
use App\Filament\Resources\VendorApplications\Pages\ListVendorApplications;
use App\Models\User;
use App\Models\VendorApplication;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\VendorSubscriptionPlanSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    (new VendorSubscriptionPlanSeeder)->run();
});

function superAdminForApplications(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can approve a pending application from the list', function () {
    $admin = superAdminForApplications();
    $application = VendorApplication::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendorApplications::class)
        ->callTableAction('approve', $application);

    $application->refresh();
    expect($application->status)->toBe(VendorApplicationStatus::Approved)
        ->and($application->vendor_id)->not->toBeNull();
});

test('approving an application with a conflicting phone number shows an error instead of crashing', function () {
    $admin = superAdminForApplications();
    User::factory()->create(['phone' => '08072750486']);
    $application = VendorApplication::factory()->create(['phone' => '08072750486']);

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendorApplications::class)
        ->callTableAction('approve', $application)
        ->assertNotified('Cannot approve: phone number "08072750486" is already used by another account. Update the phone number on this application (or on the conflicting account) before approving.');

    expect($application->fresh()->status)->toBe(VendorApplicationStatus::Pending);
});

test('an admin can reject a pending application with a reason', function () {
    Notification::fake();

    $admin = superAdminForApplications();
    $application = VendorApplication::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendorApplications::class)
        ->callTableAction('reject', $application, data: ['reason' => 'Incomplete banking info']);

    $application->refresh();
    expect($application->status)->toBe(VendorApplicationStatus::Rejected)
        ->and($application->rejection_reason)->toBe('Incomplete banking info');
});

test('approve/reject actions are hidden for an already-reviewed application', function () {
    $admin = superAdminForApplications();
    $application = VendorApplication::factory()->approved()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendorApplications::class)
        ->assertTableActionHidden('approve', $application)
        ->assertTableActionHidden('reject', $application);
});

test('a staff admin without vendors.approve cannot see the resource', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/vendor-applications')->assertForbidden();
});

test('an admin can delete a pending or rejected application', function () {
    $admin = superAdminForApplications();
    $pending = VendorApplication::factory()->create();
    $rejected = VendorApplication::factory()->create(['status' => VendorApplicationStatus::Rejected]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendorApplications::class)
        ->callTableAction('delete', $pending);

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendorApplications::class)
        ->callTableAction('delete', $rejected);

    expect(VendorApplication::find($pending->id))->toBeNull()
        ->and(VendorApplication::find($rejected->id))->toBeNull();
});

test('the delete action is hidden for an approved application', function () {
    $admin = superAdminForApplications();
    $application = VendorApplication::factory()->approved()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendorApplications::class)
        ->assertTableActionHidden('delete', $application);
});
