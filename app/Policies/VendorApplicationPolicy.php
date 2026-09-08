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

    /**
     * Deleting an Approved application is safe even though a live vendor
     * account/store exists from it: ApproveVendorApplicationAction already
     * migrates every vendor_document off vendor_application_id onto
     * vendor_id at approval time, and nothing else references this row, so
     * this only ever removes the historical "how they applied" record —
     * the vendor, store, and their documents are untouched.
     */
    public function delete(User $user, VendorApplication $application): bool
    {
        return $user->can('vendors.approve');
    }
}
