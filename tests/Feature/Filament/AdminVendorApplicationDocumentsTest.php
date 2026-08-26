<?php

use App\Filament\Resources\VendorApplications\Pages\ViewVendorApplication;
use App\Models\User;
use App\Models\VendorApplication;
use App\Models\VendorDocument;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

test('an admin can see a link to open each vendor onboarding document', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    $application = VendorApplication::factory()->create();
    $document = VendorDocument::factory()->forApplication($application)->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ViewVendorApplication::class, ['record' => $application->getKey()])
        ->assertSee('Open document')
        ->assertSee(route('vendor-documents.download', $document), false);
});
