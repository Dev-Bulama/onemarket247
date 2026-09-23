<?php

namespace App\Policies;

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\Conversation;
use App\Models\User;

/**
 * Mirrors ProductQuestionPolicy: the "other party" (a customer or an
 * admin) always has access to their own conversation; the vendor side is
 * either the vendor's own owner user or an active store staff member with
 * store.conversations.manage; an admin with conversations.moderate can do
 * anything, independent of ownership.
 */
class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->user_id === $user->id
            || $this->hasVendorAccess($user, $conversation)
            || $user->can('conversations.moderate');
    }

    public function create(User $user): bool
    {
        return in_array($user->user_type, [UserType::Customer, UserType::SuperAdmin, UserType::Admin, UserType::Staff], true);
    }

    public function reply(User $user, Conversation $conversation): bool
    {
        if ($conversation->isClosed()) {
            return false;
        }

        return $conversation->user_id === $user->id
            || $this->hasVendorAccess($user, $conversation)
            || $user->can('conversations.moderate');
    }

    public function moderate(User $user, Conversation $conversation): bool
    {
        return $user->can('conversations.moderate');
    }

    private function hasVendorAccess(User $user, Conversation $conversation): bool
    {
        $vendorId = $conversation->vendor_id;

        if ($conversation->vendor?->user_id === $user->id) {
            return true;
        }

        $isActiveStaff = $user->storeStaff()
            ->whereHas('store', fn ($query) => $query->where('vendor_id', $vendorId))
            ->where('status', StoreStaffStatus::Active)
            ->exists();

        // store.* permissions are seeded under the "vendor" guard; can()
        // only resolves them inside a request already defaulting to that
        // guard (the Filament vendor panel), not a Sanctum API request
        // (default guard "web") — see ProductPolicy/ProductQuestionPolicy
        // for the same fix, applied first.
        return $isActiveStaff && $user->checkPermissionTo('store.conversations.manage', 'vendor');
    }
}
