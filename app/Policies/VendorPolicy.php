<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;

/**
 * Encodes the vendor-isolation rule from
 * docs/architecture/01-system-architecture.md §4: a vendor may only act on
 * their own Vendor record; administrators act via explicit permissions.
 * Vendor records themselves are provisioned by approving a
 * VendorApplication (see Phase 5), not created directly through this
 * policy's create() gate.
 */
class VendorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('vendors.view');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $vendor->user_id === $user->id || $user->can('vendors.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $vendor->user_id === $user->id || $user->can('vendors.view');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.terminate');
    }

    public function approve(User $user): bool
    {
        return $user->can('vendors.approve');
    }

    public function suspend(User $user, Vendor $vendor): bool
    {
        return $user->can('vendors.suspend');
    }

    public function manageCommission(User $user): bool
    {
        return $user->can('vendors.manage_commission');
    }
}
