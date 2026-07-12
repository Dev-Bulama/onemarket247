<?php

namespace App\Policies;

use App\Models\StoreStaff;
use App\Models\User;

/**
 * Store staff management is owner-only regardless of granted permissions
 * (see docs/architecture/07-vendor-dashboard.md §3) — a vendor staff member
 * can never manage other staff even if mistakenly granted store.staff.manage.
 */
class StoreStaffPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->vendor !== null;
    }

    public function view(User $user, StoreStaff $staff): bool
    {
        return $this->isOwner($user, $staff);
    }

    public function create(User $user): bool
    {
        return $user->vendor !== null;
    }

    public function update(User $user, StoreStaff $staff): bool
    {
        return $this->isOwner($user, $staff);
    }

    public function delete(User $user, StoreStaff $staff): bool
    {
        return $this->isOwner($user, $staff);
    }

    private function isOwner(User $user, StoreStaff $staff): bool
    {
        return $staff->store->vendor->user_id === $user->id;
    }
}
