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
