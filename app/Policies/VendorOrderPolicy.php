<?php

namespace App\Policies;

use App\Enums\StoreStaffStatus;
use App\Models\User;
use App\Models\VendorOrder;

/**
 * Mirrors ProductPolicy's owner-or-permissioned-staff pattern: the vendor
 * owner always has full access to their own vendor_orders. A store staff
 * member can view with store.orders.manage, but needs the more specific
 * store.orders.fulfil to actually change a vendor order's status or cancel
 * it (see App\Actions\Order\{UpdateVendorOrderStatusAction,CancelVendorOrderAction},
 * wired up from the vendor panel's ViewVendorOrder page). Admin access is
 * entirely permission-gated, independent of ownership.
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

    public function update(User $user, VendorOrder $vendorOrder): bool
    {
        return $this->hasVendorOrderAccess($user, $vendorOrder, 'store.orders.fulfil') || $user->can('orders.manage');
    }

    private function hasVendorOrderAccess(User $user, VendorOrder $vendorOrder, ?string $storePermission = null): bool
    {
        if ($vendorOrder->vendor?->user_id === $user->id) {
            return true;
        }

        $isActiveStaff = $user->storeStaff()
            ->whereHas('store', fn ($query) => $query->where('vendor_id', $vendorOrder->vendor_id))
            ->where('status', StoreStaffStatus::Active)
            ->exists();

        if (! $isActiveStaff) {
            return false;
        }

        return $storePermission === null
            ? $user->can('store.orders.manage')
            : $user->can($storePermission);
    }
}
