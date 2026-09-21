<?php

namespace App\Notifications;

use App\Models\VendorApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the platform's own inbox a new vendor application needs review —
 * same "route to config('mail.from.address')" pattern as
 * ContactMessageSubmittedNotification, since there's no single "admin
 * user" to notify and this isn't gated by a permission the way in-app
 * notifications are.
 */
class NewVendorApplicationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly VendorApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New vendor application: '.$this->application->store_name)
            ->greeting('A new vendor application was submitted.')
            ->line('Applicant: '.$this->application->full_name)
            ->line('Reference number: '.$this->application->reference_number)
            ->line('Submitted on: '.$this->application->created_at->format('F j, Y'))
            ->line('Status: '.$this->application->status->getLabel())
            ->line('Next steps: review the application and its documents in the admin panel.')
            ->action('Review application', url('/admin/vendor-applications/'.$this->application->id));
    }
}
