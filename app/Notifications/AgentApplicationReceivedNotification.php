<?php

namespace App\Notifications;

use App\Models\AgentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgentApplicationReceivedNotification extends Notification
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
            ->subject('We received your OneMarket247 agent application')
            ->greeting('Hello '.$this->application->full_name.',')
            ->line('Thanks for applying to become a OneMarket247 field agent.')
            ->line('Reference number: '.$this->application->reference_number)
            ->line('Submitted on: '.$this->application->created_at->format('F j, Y'))
            ->line('Status: '.$this->application->status->getLabel())
            ->line("Next steps: our team will review your application and documents, and you'll get another email as soon as a decision is made.");
    }
}
