<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\VendorApplication;
use App\Support\Mail\EmailTemplateKeys;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorApplicationReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly VendorApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::active(EmailTemplateKeys::VendorApplicationReceived);

        if ($template) {
            $rendered = $template->render([
                'applicant_name' => $this->application->full_name,
                'store_name' => $this->application->store_name,
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        return (new MailMessage)
            ->subject('We received your OneMarket247 vendor application')
            ->greeting('Hello '.$this->application->full_name.',')
            ->line('Thanks for applying to sell on OneMarket247 as "'.$this->application->store_name.'".')
            ->line('Our team will review your application and documents, and you\'ll get another email as soon as a decision is made.');
    }
}
