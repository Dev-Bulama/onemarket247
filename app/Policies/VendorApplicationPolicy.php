<?php

namespace App\Policies;

use App\Enums\VendorApplicationStatus;
use App\Models\User;
use App\Models\VendorApplication;

/**
 * Applications are created by unauthenticated applicants via the public
 * registration wizard (see App\Livewire\VendorRegistrationForm), never
 * through this policy — create() is hard-false. Review is admin-only.
 */
class VendorApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vendors.approve');
    }

    public function view(User $user, VendorApplication $application): bool
    {
        return $user->can('vendors.approve');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, VendorApplication $application): bool
    {
        return $user->can('vendors.approve');
    }

    /**
     * Never for an Approved application — a live vendor account, store,
     * and (as of the vendor_application_id fix in
     * ApproveVendorApplicationAction) the vendor's own real documents can
     * depend on it. Pending/Rejected applications have nothing else
     * referencing them, so deleting is safe.
     */
    public function delete(User $user, VendorApplication $application): bool
    {
        return $user->can('vendors.approve')
            && $application->status !== VendorApplicationStatus::Approved;
    }
}
