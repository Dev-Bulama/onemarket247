<?php

namespace App\Listeners\Vendor;

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use Illuminate\Auth\Events\Login;

/**
 * A staff invite creates the StoreStaff row with status "invited" (see
 * App\Actions\Vendor\InviteStoreStaffAction); the row only earns "active"
 * once the invitee actually sets a password and signs in for the first
 * time, confirming the invite email reached a real person.
 */
class ActivateInvitedStoreStaff
{
    public function handle(Login $event): void
    {
        if ($event->guard !== 'vendor' || $event->user->user_type !== UserType::VendorStaff) {
            return;
        }

        $event->user->storeStaff()
            ->where('status', StoreStaffStatus::Invited)
            ->update(['status' => StoreStaffStatus::Active, 'joined_at' => now()]);
    }
}
