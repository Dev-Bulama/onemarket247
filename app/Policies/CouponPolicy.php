<?php

namespace App\Policies;

use App\Enums\StoreStaffStatus;
use App\Models\Coupon;
use App\Models\User;

/**
 * Mirrors ProductPolicy's owner-or-permissioned-staff pattern for a
 * vendor's own coupons (vendor_id set — a platform-wide, admin-created
 * coupon has no owner here and is never reachable through this policy
 * since CouponResource::getEloquentQuery() already excludes them).
 */
class CouponPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->vendor !== null || $user->storeStaff()->exists();
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $this->hasCouponAccess($user, $coupon);
    }

    public function create(User $user): bool
    {
        return $this->hasVendorWriteAccess($user);
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $this->hasCouponAccess($user, $coupon, 'store.coupons.manage');
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $this->hasCouponAccess($user, $coupon, 'store.coupons.manage');
    }

    /**
     * store.* permissions are seeded under the "vendor" guard, so
     * $user->can() only resolves them inside a request already defaulting
     * to that guard (the Filament vendor panel) — see ProductPolicy's
     * identical note. checkPermissionTo() with an explicit guard is immune
     * to that, so it's used here even though this policy is only ever
     * reached from the vendor panel today.
     */
    private function hasCouponAccess(User $user, Coupon $coupon, ?string $storePermission = null): bool
    {
        if ($coupon->vendor?->user_id === $user->id) {
            return true;
        }

        $isActiveStaff = $user->storeStaff()
            ->whereHas('store', fn ($query) => $query->where('vendor_id', $coupon->vendor_id))
            ->where('status', StoreStaffStatus::Active)
            ->exists();

        if (! $isActiveStaff) {
            return false;
        }

        return $storePermission === null || $user->checkPermissionTo($storePermission, 'vendor');
    }

    private function hasVendorWriteAccess(User $user): bool
    {
        if ($user->vendor !== null) {
            return true;
        }

        return $user->storeStaff()
            ->where('status', StoreStaffStatus::Active)
            ->exists() && $user->checkPermissionTo('store.coupons.manage', 'vendor');
    }
}
