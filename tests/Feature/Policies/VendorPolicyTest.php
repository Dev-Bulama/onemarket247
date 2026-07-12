<?php

use App\Models\User;
use App\Models\Vendor;
use Spatie\Permission\Models\Permission;

test('a vendor owner can view and update their own vendor record', function () {
    $vendor = Vendor::factory()->create();

    expect($vendor->user->can('view', $vendor))->toBeTrue()
        ->and($vendor->user->can('update', $vendor))->toBeTrue();
});

test('an unrelated user cannot view or update someone else\'s vendor record', function () {
    $vendor = Vendor::factory()->create();
    $stranger = User::factory()->create();

    expect($stranger->can('view', $vendor))->toBeFalse()
        ->and($stranger->can('update', $vendor))->toBeFalse();
});

test('an admin with the vendors.view permission can view any vendor', function () {
    Permission::findOrCreate('vendors.view', 'web');

    $vendor = Vendor::factory()->create();
    $admin = User::factory()->admin()->create();
    $admin->givePermissionTo('vendors.view');

    expect($admin->can('view', $vendor))->toBeTrue();
});

test('no one may create a vendor directly through the policy', function () {
    $user = User::factory()->create();

    expect($user->can('create', Vendor::class))->toBeFalse();
});
