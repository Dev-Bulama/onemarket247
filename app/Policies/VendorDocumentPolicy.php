<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VendorDocument;

class VendorDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->vendor !== null || $user->storeStaff()->exists() || $user->can('vendors.view');
    }

    public function view(User $user, VendorDocument $document): bool
    {
        return $this->belongsToActor($user, $document) || $user->can('vendors.view');
    }

    public function create(User $user): bool
    {
        return $user->vendor !== null;
    }

    public function update(User $user, VendorDocument $document): bool
    {
        return $user->can('vendors.approve');
    }

    public function delete(User $user, VendorDocument $document): bool
    {
        return $user->can('vendors.approve');
    }

    private function belongsToActor(User $user, VendorDocument $document): bool
    {
        return $document->vendor_id !== null && $document->vendor_id === $user->actingVendorId();
    }
}
