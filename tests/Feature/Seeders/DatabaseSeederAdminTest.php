<?php

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('the seeded test super admin is assigned the Super Admin role so admin resources are visible', function () {
    (new DatabaseSeeder)->run();

    $admin = User::where('email', 'admin@onemarket247.test')->firstOrFail();

    expect($admin->hasRole('Super Admin', 'admin'))->toBeTrue();
});

test('re-running the seeder backfills the Super Admin role onto an admin user created without one', function () {
    // Simulates a production admin created via a manual User::create() /
    // tinker call before the role-assignment was wired up in this seeder.
    User::create([
        'name' => 'Test Super Admin',
        'email' => 'admin@onemarket247.test',
        'email_verified_at' => now(),
        'password' => bcrypt('password'),
        'user_type' => UserType::SuperAdmin,
        'status' => UserStatus::Active,
    ]);

    (new DatabaseSeeder)->run();

    $admin = User::where('email', 'admin@onemarket247.test')->firstOrFail();

    expect($admin->hasRole('Super Admin', 'admin'))->toBeTrue();
});

test('re-running the seeder does not duplicate the admin user', function () {
    (new DatabaseSeeder)->run();
    (new DatabaseSeeder)->run();

    expect(User::where('email', 'admin@onemarket247.test')->count())->toBe(1);
});
