<?php

namespace App\Policies;

use App\Models\CustomerProfile;
use App\Models\User;

class CustomerProfilePolicy
{
    public function view(User $user, CustomerProfile $profile): bool
    {
        return $profile->user_id === $user->id || $user->can('customers.view');
    }

    public function update(User $user, CustomerProfile $profile): bool
    {
        return $profile->user_id === $user->id || $user->can('customers.manage');
    }
}
