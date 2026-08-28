<?php

namespace App\Policies;

use App\Enums\StoreStaffStatus;
use App\Models\StockTransfer;
use App\Models\User;

/**
 * A transfer always moves stock between two warehouses of the same vendor
 * (enforced by App\Actions\Inventory\InitiateStockTransferAction), so access
 * is checked against the source warehouse's vendor — mirrors
 * WarehousePolicy's owner-or-permissioned-staff pattern.
 */
class StockTransferPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->vendor !== null || $user->storeStaff()->exists() || $user->can('inventory.manage');
    }

    public function view(User $user, StockTransfer $transfer): bool
    {
        return $this->hasTransferAccess($user, $transfer) || $user->can('inventory.manage');
    }

    public function create(User $user): bool
    {
        return $this->hasVendorWriteAccess($user) || $user->can('inventory.manage');
    }

    public function update(User $user, StockTransfer $transfer): bool
    {
        return $this->hasTransferAccess($user, $transfer, 'store.inventory.manage') || $user->can('inventory.manage');
    }

    private function hasTransferAccess(User $user, StockTransfer $transfer, ?string $storePermission = null): bool
    {
        $vendorId = $transfer->fromWarehouse->vendor_id;

        if ($transfer->fromWarehouse->vendor?->user_id === $user->id) {
            return true;
        }

        $isActiveStaff = $user->storeStaff()
            ->whereHas('store', fn ($query) => $query->where('vendor_id', $vendorId))
            ->where('status', StoreStaffStatus::Active)
            ->exists();

        if (! $isActiveStaff) {
            return false;
        }

        return $storePermission === null || $user->can($storePermission);
    }

    private function hasVendorWriteAccess(User $user): bool
    {
        if ($user->vendor !== null) {
            return true;
        }

        return $user->storeStaff()
            ->where('status', StoreStaffStatus::Active)
            ->exists() && $user->can('store.inventory.manage');
    }
}
