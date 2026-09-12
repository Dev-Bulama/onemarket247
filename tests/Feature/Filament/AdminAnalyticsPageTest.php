<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

test('an admin with analytics.view can load the analytics page', function () {
    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    Order::factory()->create(['status' => OrderStatus::Paid, 'total' => 5000]);

    $this->actingAs($admin, 'admin')->get('/admin/analytics')->assertOk();
});

test('an admin without analytics.view cannot access the analytics page', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/analytics')->assertForbidden();
});
