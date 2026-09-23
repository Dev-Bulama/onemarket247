<?php

namespace App\Notifications;

use App\Models\AgentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgentApplicationRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly AgentApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Update on your OneMarket247 agent application')
            ->greeting('Hello '.$this->application->full_name.',')
            ->line('Thanks for your interest in becoming a OneMarket247 field agent.')
            ->line("Unfortunately, we're unable to approve your application at this time.")
            ->line('Reason: '.$this->application->rejection_reason)
            ->line('Reference number: '.$this->application->reference_number);
    }
}
