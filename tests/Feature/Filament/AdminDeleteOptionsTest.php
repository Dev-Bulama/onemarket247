<?php

use App\Actions\Vendor\ApproveVendorApplicationAction;
use App\Enums\VendorApplicationStatus;
use App\Filament\Resources\Stores\Pages\ListStores;
use App\Filament\Resources\VendorApplications\Pages\ListVendorApplications;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function deleteOptionsAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('bulk-deleting vendor applications works regardless of status, including approved ones', function () {
    $admin = deleteOptionsAdmin();

    $pending = VendorApplication::factory()->create(['status' => VendorApplicationStatus::Pending]);
    $rejected = VendorApplication::factory()->create(['status' => VendorApplicationStatus::Rejected]);
    $approved = VendorApplication::factory()->create(['status' => VendorApplicationStatus::Approved]);

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendorApplications::class)
        ->callTableBulkAction('delete', [$pending, $rejected, $approved]);

    expect(VendorApplication::find($pending->id))->toBeNull()
        ->and(VendorApplication::find($rejected->id))->toBeNull()
        ->and(VendorApplication::find($approved->id))->toBeNull();
});

test('deleting an approved application does not touch the live vendor it created', function () {
    $admin = deleteOptionsAdmin();

    $application = VendorApplication::factory()->create();
    $vendor = app(ApproveVendorApplicationAction::class)->handle($application, $admin);

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendorApplications::class)
        ->callTableAction('delete', $application->fresh());

    expect(VendorApplication::find($application->id))->toBeNull()
        ->and(Vendor::find($vendor->id))->not->toBeNull()
        ->and($vendor->fresh()->store)->not->toBeNull();
});

test('force delete is visible next to delete on an active vendor row, while restore stays hidden until suspended', function () {
    $admin = deleteOptionsAdmin();
    $vendor = Vendor::factory()->create();

    Filament::setCurrentPanel('admin');

    $component = Livewire::actingAs($admin, 'admin')->test(ListVendors::class);
    $component->assertTableActionVisible('delete', record: $vendor)
        ->assertTableActionVisible('forceDelete', record: $vendor)
        ->assertTableActionHidden('restore', record: $vendor);

    $component->callTableAction('delete', $vendor);

    $component->assertTableActionHidden('delete', record: $vendor->fresh())
        ->assertTableActionVisible('forceDelete', record: $vendor->fresh())
        ->assertTableActionVisible('restore', record: $vendor->fresh());
});

test('force-deleting a vendor directly from the list frees its email for a new registration, with no prior suspension needed', function () {
    $admin = deleteOptionsAdmin();
    $vendor = Vendor::factory()->create();
    $email = $vendor->user->email;

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendors::class)
        ->callTableAction('forceDelete', $vendor);

    expect(Vendor::withTrashed()->find($vendor->id))->toBeNull()
        ->and(User::where('email', $email)->exists())->toBeFalse();
});

test('an admin can delete a single store from the stores list', function () {
    $admin = deleteOptionsAdmin();
    $store = Store::factory()->create();

    Filament::setCurrentPanel('admin');

    Livewire::actingAs($admin, 'admin')
        ->test(ListStores::class)
        ->callTableAction('delete', $store);

    expect(Store::withoutGlobalScopes()->find($store->id)->trashed())->toBeTrue();
});
