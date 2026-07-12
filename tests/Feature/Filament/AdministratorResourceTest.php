<?php

use App\Enums\UserType;
use App\Filament\Resources\Administrators\AdministratorResource;
use App\Filament\Resources\Administrators\Pages\CreateAdministrator;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

test('an administrator cannot delete their own account', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());
    $this->actingAs($admin, 'admin');

    expect(AdministratorResource::canDelete($admin))->toBeFalse();
});

test('an administrator can delete a different administrator', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());
    $this->actingAs($admin, 'admin');

    $other = User::factory()->admin()->create();

    expect(AdministratorResource::canDelete($other))->toBeTrue();
});

test('creating an administrator through the panel marks their email as verified', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    Livewire::actingAs($admin, 'admin')
        ->test(CreateAdministrator::class)
        ->fillForm([
            'name' => 'New Staffer',
            'email' => 'staffer@example.com',
            'user_type' => UserType::Staff->value,
            'password' => 'Password123!',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::where('email', 'staffer@example.com')->firstOrFail();

    expect($created->email_verified_at)->not->toBeNull()
        ->and($created->user_type)->toBe(UserType::Staff);
});
