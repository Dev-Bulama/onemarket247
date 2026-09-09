<?php

namespace App\Actions\Admin;

use App\Enums\AdminMessageAudience;
use App\Enums\UserType;
use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class SendAdminMessageAction
{
    /**
     * @param  array<int, int>  $userIds  only used when $audience is Specific
     * @return int the number of recipients the broadcast was actually sent to
     */
    public function handle(
        AdminMessageAudience $audience,
        string $subject,
        string $body,
        array $userIds = [],
        ?User $sender = null,
    ): int {
        $recipients = $this->resolveAudience($audience, $userIds);
        $notification = new AdminBroadcastNotification($subject, $body, $sender?->name);
        $sent = 0;

        // Sent one recipient at a time, each in its own try/catch:
        // Notification::send() re-throws on the first failed channel send
        // (see Illuminate\Notifications\NotificationSender::sendToNotifiable),
        // so a single bad mailbox or unregistered push device would
        // otherwise silently abort the whole broadcast, leaving every
        // recipient after it never notified at all.
        foreach ($recipients as $recipient) {
            try {
                Notification::send($recipient, $notification);
                $sent++;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $sent;
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
