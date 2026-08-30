<?php

namespace App\Policies;

use App\Enums\StoreStaffStatus;
use App\Models\User;
use App\Models\Warehouse;

/**
 * Mirrors ProductPolicy's owner-or-permissioned-staff pattern: the vendor
 * owner always has full access to their own warehouses; a vendor staff
 * member needs the matching store.inventory.manage permission on top of
 * being an active staff member of that vendor's store. Admin access is
 * entirely permission-gated, independent of ownership.
 */
class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->vendor !== null || $user->storeStaff()->exists() || $user->can('warehouses.manage');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $this->hasWarehouseAccess($user, $warehouse) || $user->can('warehouses.manage');
    }

    public function create(User $user): bool
    {
        return $this->hasVendorWriteAccess($user) || $user->can('warehouses.manage');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $this->hasWarehouseAccess($user, $warehouse, 'store.inventory.manage') || $user->can('warehouses.manage');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $this->hasWarehouseAccess($user, $warehouse, 'store.inventory.manage') || $user->can('warehouses.manage');
    }

    private function hasWarehouseAccess(User $user, Warehouse $warehouse, ?string $storePermission = null): bool
    {
        if ($warehouse->vendor?->user_id === $user->id) {
            return true;
        }

        $isActiveStaff = $user->storeStaff()
            ->whereHas('store', fn ($query) => $query->where('vendor_id', $warehouse->vendor_id))
            ->where('status', StoreStaffStatus::Active)
            ->exists();

        if (! $isActiveStaff) {
            return false;
        }

        // store.* permissions are seeded under the "vendor" guard; can()
        // only resolves them inside a request already defaulting to that
        // guard (the Filament vendor panel), not a Sanctum API request
        // (default guard "web") — see ProductPolicy for the same fix,
        // applied first.
        return $storePermission === null || $user->checkPermissionTo($storePermission, 'vendor');
    }

    private function hasVendorWriteAccess(User $user): bool
    {
        if ($user->vendor !== null) {
            return true;
        }

        return $user->storeStaff()
            ->where('status', StoreStaffStatus::Active)
            ->exists() && $user->checkPermissionTo('store.inventory.manage', 'vendor');
    }
}
