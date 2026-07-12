<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function adminWithRole(string $roleName): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', $roleName)->where('guard_name', 'admin')->first());

    return $user;
}

test('guests are redirected to the admin login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

test('a customer cannot reach the admin panel', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer, 'web')->get('/admin')->assertRedirect('/admin/login');
});

test('a super admin can access every Phase 4 resource index page', function () {
    $admin = adminWithRole('Super Admin');

    $pages = [
        '/admin/administrators',
        '/admin/roles',
        '/admin/customers',
        '/admin/vendors',
        '/admin/stores',
        '/admin/countries',
        '/admin/states',
        '/admin/cities',
        '/admin/languages',
        '/admin/currencies',
        '/admin/exchange-rates',
        '/admin/settings',
        '/admin/audit-logs',
    ];

    foreach ($pages as $page) {
        $this->actingAs($admin, 'admin')->get($page)->assertOk();
    }
});

test('a staff admin without the relevant permission is forbidden', function () {
    $staff = adminWithRole('Support Staff');

    $this->actingAs($staff, 'admin')->get('/admin/administrators')->assertForbidden();
});

test('a staff admin with a relevant permission preset can access their scoped resource', function () {
    // Catalog Staff's preset doesn't include any Phase 4 permission either
    // (its permissions - products/categories/brands/attributes - all land
    // in Phase 6), so it should be forbidden from every current resource.
    $catalogStaff = adminWithRole('Catalog Staff');

    $this->actingAs($catalogStaff, 'admin')->get('/admin/vendors')->assertForbidden();

    // Support Staff *does* include customers.view, so it should reach the
    // Customers resource even without admins.manage.
    $supportStaff = adminWithRole('Support Staff');

    $this->actingAs($supportStaff, 'admin')->get('/admin/customers')->assertOk();
});
