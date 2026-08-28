<?php

namespace App\Policies;

use App\Enums\StoreStaffStatus;
use App\Models\Product;
use App\Models\User;

/**
 * Mirrors StorePolicy's owner-or-permissioned-staff pattern: a vendor owner
 * always has full access to their own products; a vendor staff member
 * needs the matching store.products.manage permission on top of being an
 * active staff member of that vendor's store. Admin access is entirely
 * permission-gated, independent of ownership.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->vendor !== null || $user->storeStaff()->exists() || $user->can('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->hasProductAccess($user, $product) || $user->can('products.view');
    }

    public function create(User $user): bool
    {
        return $this->hasVendorWriteAccess($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->hasProductAccess($user, $product, 'store.products.manage') || $user->can('products.update');
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->hasProductAccess($user, $product, 'store.products.manage') || $user->can('products.delete');
    }

    public function approve(User $user): bool
    {
        return $user->can('products.approve');
    }

    public function feature(User $user): bool
    {
        return $user->can('products.feature');
    }

    private function hasProductAccess(User $user, Product $product, ?string $storePermission = null): bool
    {
        if ($product->vendor?->user_id === $user->id) {
            return true;
        }

        $isActiveStaff = $user->storeStaff()
            ->whereHas('store', fn ($query) => $query->where('vendor_id', $product->vendor_id))
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
            ->exists() && $user->can('store.products.manage');
    }
}
