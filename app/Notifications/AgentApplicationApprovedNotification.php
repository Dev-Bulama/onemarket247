<?php

namespace App\Notifications;

use App\Models\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Purely informational — unlike an approved vendor, an agent has no
 * account or panel to log into (see App\Models\Agent's docblock), so this
 * never mints a password-reset token the way
 * VendorApplicationApprovedNotification does.
 */
class AgentApplicationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Agent $agent) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your OneMarket247 agent application was approved')
            ->greeting('Congratulations '.$this->agent->full_name.'!')
            ->line("You're now a registered OneMarket247 field agent.")
            ->line('Vendors you assist can now select you from the "Registered Agent" list when they apply to sell on OneMarket247.')
            ->line('If your contact details ever change, please let us know so vendors can always reach you.');
    }
}
