<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Product $product) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your product was not approved')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your product "'.$this->product->name.'" was not approved for listing.')
            ->when($this->product->rejection_reason, fn (MailMessage $mail) => $mail->line('Reason: '.$this->product->rejection_reason))
            ->line('You can update the listing and resubmit it for review.');
    }
}
