<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $name,
        private readonly string $email,
        private readonly string $subject,
        private readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New contact message: '.$this->subject)
            ->greeting('New contact form submission')
            ->line('From: '.$this->name.' <'.$this->email.'>')
            ->line('Subject: '.$this->subject)
            ->line($this->message)
            ->replyTo($this->email, $this->name);
    }
}
