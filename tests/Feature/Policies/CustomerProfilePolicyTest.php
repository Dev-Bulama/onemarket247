<?php

use App\Models\CustomerProfile;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('a customer can view and update their own profile', function () {
    $user = User::factory()->create();
    $profile = CustomerProfile::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $profile))->toBeTrue()
        ->and($user->can('update', $profile))->toBeTrue();
});

test('another customer cannot view or update someone else\'s profile', function () {
    $profile = CustomerProfile::factory()->create();
    $stranger = User::factory()->create();

    expect($stranger->can('view', $profile))->toBeFalse()
        ->and($stranger->can('update', $profile))->toBeFalse();
});

test('an admin with customers.manage can view and update any profile', function () {
    Permission::findOrCreate('customers.manage', 'web');
    Permission::findOrCreate('customers.view', 'web');

    $profile = CustomerProfile::factory()->create();
    $admin = User::factory()->admin()->create();
    $admin->givePermissionTo(['customers.manage', 'customers.view']);

    expect($admin->can('view', $profile))->toBeTrue()
        ->and($admin->can('update', $profile))->toBeTrue();
});
