<?php

namespace App\Policies;

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

    public function delete(User $user, VendorApplication $application): bool
    {
        return false;
    }
}
