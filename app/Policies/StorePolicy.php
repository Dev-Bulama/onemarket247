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

    /**
     * store.* permissions are seeded under the "vendor" guard (see
     * RolePermissionSeeder); $user->can() only resolves them correctly
     * inside a request whose default auth guard is already "vendor" (the
     * Filament vendor panel) — a Sanctum API request's default guard is
     * "web", where can() silently returns false for a correctly-
     * permissioned staff member. checkPermissionTo() with an explicit
     * guard is immune to that.
     */
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

        return $storePermission === null || $user->checkPermissionTo($storePermission, 'vendor');
    }
}
