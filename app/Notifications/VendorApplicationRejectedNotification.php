<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\VendorApplication;
use App\Support\Mail\EmailTemplateKeys;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorApplicationRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly VendorApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::active(EmailTemplateKeys::VendorApplicationRejected);

        if ($template) {
            $rendered = $template->render([
                'applicant_name' => $this->application->full_name,
                'store_name' => $this->application->store_name,
                'rejection_reason' => $this->application->rejection_reason ?? '',
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        return (new MailMessage)
            ->subject('Your OneMarket247 vendor application')
            ->greeting('Hello '.$this->application->full_name.',')
            ->line('Thank you for applying to sell on OneMarket247.')
            ->line('After review, we\'re unable to approve your application for "'.$this->application->store_name.'" at this time.')
            ->when($this->application->rejection_reason, fn (MailMessage $mail) => $mail->line('Reason: '.$this->application->rejection_reason))
            ->line('You\'re welcome to submit a new application once the issue above has been addressed.');
    }
}
