<?php

namespace App\Policies;

use App\Enums\StoreStaffStatus;
use App\Models\User;
use App\Models\VendorOrder;

/**
 * Mirrors WarehousePolicy's owner-or-permissioned-staff pattern: the
 * vendor owner always has full access to their own vendor_orders; a store
 * staff member needs store.orders.manage on top of active status. Admin
 * access is entirely permission-gated, independent of ownership.
 */
class VendorOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->vendor !== null || $user->storeStaff()->exists() || $user->can('orders.view');
    }

    public function view(User $user, VendorOrder $vendorOrder): bool
    {
        return $this->hasVendorOrderAccess($user, $vendorOrder) || $user->can('orders.view');
    }

    private function hasVendorOrderAccess(User $user, VendorOrder $vendorOrder): bool
    {
        if ($vendorOrder->vendor->user_id === $user->id) {
            return true;
        }

        $isActiveStaff = $user->storeStaff()
            ->whereHas('store', fn ($query) => $query->where('vendor_id', $vendorOrder->vendor_id))
            ->where('status', StoreStaffStatus::Active)
            ->exists();

        return $isActiveStaff && $user->can('store.orders.manage');
    }
}
