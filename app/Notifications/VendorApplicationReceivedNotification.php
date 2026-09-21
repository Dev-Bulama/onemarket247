<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\VendorApplication;
use App\Notifications\Channels\AfricasTalkingChannel;
use App\Notifications\Messages\SmsMessage;
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
        return ['mail', AfricasTalkingChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $template = EmailTemplate::active(EmailTemplateKeys::VendorApplicationReceived);

        if ($template) {
            $rendered = $template->render([
                'applicant_name' => $this->application->full_name,
                'store_name' => $this->application->store_name,
                'reference_number' => $this->application->reference_number,
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body']);
        }

        return (new MailMessage)
            ->subject('We received your OneMarket247 vendor application')
            ->greeting('Hello '.$this->application->full_name.',')
            ->line('Thanks for applying to sell on OneMarket247 as "'.$this->application->store_name.'".')
            ->line('Reference number: '.$this->application->reference_number)
            ->line('Submitted on: '.$this->application->created_at->format('F j, Y'))
            ->line('Status: '.$this->application->status->getLabel())
            ->line('Next steps: our team will review your application and documents, and you\'ll get another email as soon as a decision is made.');
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return SmsMessage::create(
            $this->application->phone ?? '',
            "OneMarket247: We received your vendor application (Ref: {$this->application->reference_number}). We'll notify you once it's reviewed.",
        );
    }
}
