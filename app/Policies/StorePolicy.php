<?php

namespace App\Policies;

use App\Enums\StoreStaffStatus;
use App\Models\Store;
use App\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('stores.manage');
    }

    public function view(User $user, Store $store): bool
    {
        return $this->hasStoreAccess($user, $store) || $user->can('stores.manage');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Store $store): bool
    {
        return $this->hasStoreAccess($user, $store, 'store.settings.manage') || $user->can('stores.manage');
    }

    public function delete(User $user, Store $store): bool
    {
        return $user->can('stores.manage');
    }

    private function hasStoreAccess(User $user, Store $store, ?string $storePermission = null): bool
    {
        if ($store->vendor?->user_id === $user->id) {
            return true;
        }

        $isActiveStaff = $store->staff()
            ->where('user_id', $user->id)
            ->where('status', StoreStaffStatus::Active)
            ->exists();

        if (! $isActiveStaff) {
            return false;
        }

        return $storePermission === null || $user->can($storePermission);
    }
}
