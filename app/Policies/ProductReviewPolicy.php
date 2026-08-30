<?php

namespace App\Policies;

use App\Enums\ReviewStatus;
use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\ProductReview;
use App\Models\User;

/**
 * Mirrors the owner-or-permissioned-staff pattern used throughout the
 * catalog/inventory policies. A customer owns their own review while it is
 * still pending; a vendor (or staff with store.reviews.respond) can respond
 * to reviews on their own store's products; an admin with reviews.moderate
 * can do anything, independent of ownership.
 */
class ProductReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reviews.moderate') || $user->storeStaff()->exists() || $user->vendor !== null;
    }

    public function view(User $user, ProductReview $review): bool
    {
        return $review->customer_id === $user->id
            || $this->hasStoreReviewAccess($user, $review)
            || $user->can('reviews.moderate');
    }

    public function create(User $user): bool
    {
        return $user->user_type === UserType::Customer;
    }

    public function update(User $user, ProductReview $review): bool
    {
        if ($review->customer_id === $user->id) {
            return $review->status === ReviewStatus::Pending;
        }

        return $this->hasStoreReviewAccess($user, $review) || $user->can('reviews.moderate');
    }

    public function delete(User $user, ProductReview $review): bool
    {
        return $review->customer_id === $user->id || $user->can('reviews.moderate');
    }

    public function moderate(User $user): bool
    {
        return $user->can('reviews.moderate');
    }

    private function hasStoreReviewAccess(User $user, ProductReview $review): bool
    {
        $vendorId = $review->product->vendor_id;

        if ($review->product->vendor?->user_id === $user->id) {
            return true;
        }

        $isActiveStaff = $user->storeStaff()
            ->whereHas('store', fn ($query) => $query->where('vendor_id', $vendorId))
            ->where('status', StoreStaffStatus::Active)
            ->exists();

        // store.* permissions are seeded under the "vendor" guard; can()
        // only resolves them inside a request already defaulting to that
        // guard (the Filament vendor panel), not a Sanctum API request
        // (default guard "web") — see ProductPolicy for the same fix,
        // applied first.
        return $isActiveStaff && $user->checkPermissionTo('store.reviews.respond', 'vendor');
    }
}
