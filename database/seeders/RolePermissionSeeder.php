<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds the canonical permission list and role presets from
 * docs/architecture/03-modules-and-roles.md §3-4. Route/Policy/Filament
 * wiring against these permissions lands as each owning phase builds the
 * feature; this seeder only establishes the vocabulary.
 */
class RolePermissionSeeder extends Seeder
{
    private const PERMISSIONS = [
        'admins.manage', 'staff.manage', 'roles.manage',
        'vendors.view', 'vendors.approve', 'vendors.suspend', 'vendors.terminate', 'vendors.manage_commission',
        'stores.manage',
        'customers.view', 'customers.manage',
        'products.view', 'products.create', 'products.update', 'products.delete', 'products.approve', 'products.feature',
        'categories.manage', 'brands.manage', 'attributes.manage',
        'inventory.manage', 'warehouses.manage',
        'orders.view', 'orders.manage', 'orders.export',
        'payments.view', 'payments.manage', 'refunds.manage',
        'commissions.manage', 'withdrawals.view', 'withdrawals.approve',
        'shipping.manage', 'taxes.manage',
        'coupons.manage', 'flash_sales.manage',
        'returns.manage', 'disputes.manage',
        'cms.manage', 'blog.manage', 'menus.manage',
        'seo.manage', 'analytics.view',
        'newsletter.manage',
        'translations.manage', 'currencies.manage', 'languages.manage',
        'notifications.manage', 'email_templates.manage', 'smtp.manage',
        'support.manage',
        'reports.view',
        'settings.manage', 'security.manage',
        'backups.manage', 'logs.view', 'system_health.view',
    ];

    private const STORE_PERMISSIONS = [
        'store.products.manage', 'store.inventory.manage', 'store.orders.manage',
        'store.orders.fulfil', 'store.coupons.manage', 'store.reviews.respond',
        'store.settings.manage', 'store.staff.manage', 'store.reports.view',
        'store.withdrawals.request',
    ];

    private const ADMIN_ROLE_PRESETS = [
        'Super Admin' => '*',
        'Admin' => self::PERMISSIONS,
        'Catalog Staff' => ['products.view', 'products.create', 'products.update', 'products.approve', 'categories.manage', 'brands.manage', 'attributes.manage'],
        'Support Staff' => ['support.manage', 'customers.view', 'orders.view', 'returns.manage', 'disputes.manage'],
        'Finance Staff' => ['payments.view', 'payments.manage', 'refunds.manage', 'commissions.manage', 'withdrawals.view', 'withdrawals.approve', 'reports.view'],
    ];

    private const VENDOR_ROLE_PRESETS = [
        'Vendor Owner' => '*',
        'Vendor Staff - Products' => ['store.products.manage', 'store.inventory.manage'],
        'Vendor Staff - Orders' => ['store.orders.manage', 'store.orders.fulfil'],
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        foreach (self::STORE_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'vendor');
        }

        foreach (self::ADMIN_ROLE_PRESETS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'admin');

            if ($permissions === '*') {
                $role->syncPermissions(Permission::where('guard_name', 'admin')->get());
            } else {
                $role->syncPermissions($permissions);
            }
        }

        foreach (self::VENDOR_ROLE_PRESETS as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'vendor');

            if ($permissions === '*') {
                $role->syncPermissions(Permission::where('guard_name', 'vendor')->get());
            } else {
                $role->syncPermissions($permissions);
            }
        }
    }
}
