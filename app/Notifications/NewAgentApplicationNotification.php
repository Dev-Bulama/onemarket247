<?php

namespace App\Notifications;

use App\Models\AgentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the platform's own inbox a new agent application needs review —
 * same "route to config('mail.from.address')" pattern as
 * NewVendorApplicationNotification, since there's no single "admin user"
 * to notify.
 */
class NewAgentApplicationNotification extends Notification
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
            ->subject('New agent application: '.$this->application->full_name)
            ->greeting('A new agent application was submitted.')
            ->line('Applicant: '.$this->application->full_name)
            ->line('Reference number: '.$this->application->reference_number)
            ->line('Submitted on: '.$this->application->created_at->format('F j, Y'))
            ->line('Status: '.$this->application->status->getLabel())
            ->line('Next steps: review the application and its documents in the admin panel.')
            ->action('Review application', url('/admin/agent-applications/'.$this->application->id));
    }
}
