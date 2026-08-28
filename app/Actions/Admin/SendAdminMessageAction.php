<?php

namespace App\Actions\Admin;

use App\Enums\AdminMessageAudience;
use App\Enums\UserType;
use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class SendAdminMessageAction
{
    /**
     * @param  array<int, int>  $userIds  only used when $audience is Specific
     */
    public function handle(
        AdminMessageAudience $audience,
        string $subject,
        string $body,
        array $userIds = [],
        ?User $sender = null,
    ): int {
        $recipients = $this->resolveAudience($audience, $userIds);

        Notification::send($recipients, new AdminBroadcastNotification($subject, $body, $sender?->name));

        return $recipients->count();
    }

    /**
     * @param  array<int, int>  $userIds
     * @return Collection<int, User>
     */
    private function resolveAudience(AdminMessageAudience $audience, array $userIds): Collection
    {
        return match ($audience) {
            AdminMessageAudience::AllUsers => User::whereIn('user_type', [
                UserType::Customer, UserType::VendorOwner, UserType::VendorStaff,
            ])->get(),
            AdminMessageAudience::AllCustomers => User::where('user_type', UserType::Customer)->get(),
            AdminMessageAudience::AllVendors => User::whereIn('user_type', [
                UserType::VendorOwner, UserType::VendorStaff,
            ])->get(),
            AdminMessageAudience::Specific => User::whereIn('id', $userIds)->get(),
        };
    }
}
