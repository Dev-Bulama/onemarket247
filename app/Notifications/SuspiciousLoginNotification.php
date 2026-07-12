<?php

namespace App\Notifications;

use App\Models\LoginHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuspiciousLoginNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly LoginHistory $loginHistory) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New sign-in to your OneMarket247 account')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('We noticed a sign-in to your account from a device we haven\'t seen before.')
            ->line('IP address: '.($this->loginHistory->ip_address ?? 'unknown'))
            ->line('Time: '.$this->loginHistory->created_at->toDayDateTimeString())
            ->line('If this was you, no action is needed.')
            ->line('If you don\'t recognize this activity, change your password immediately and review your active sessions.');
    }
}
