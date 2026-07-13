<?php

namespace App\Notifications;

use App\Models\ProductReview;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ProductReview $review) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your review was not approved')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your review of "'.$this->review->product->name.'" was not approved for publishing.')
            ->when($this->review->rejection_reason, fn (MailMessage $mail) => $mail->line('Reason: '.$this->review->rejection_reason));
    }
}
