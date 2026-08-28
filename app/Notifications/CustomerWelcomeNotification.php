<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Support\Mail\EmailTemplateKeys;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once, right after RegisterCustomerAction creates a new customer
 * account (web or API registration — both funnel through that one action).
 */
class CustomerWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly User $user) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('home', absolute: false));

        $template = EmailTemplate::active(EmailTemplateKeys::CustomerWelcome);

        if ($template) {
            $rendered = $template->render([
                'customer_name' => $this->user->name,
                'shop_url' => $url,
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body'])
                ->action('Start shopping', $url);
        }

        return (new MailMessage)
            ->subject('Welcome to OneMarket247!')
            ->greeting('Welcome, '.$this->user->name.'!')
            ->line('Thanks for creating an account with OneMarket247. You\'re all set to browse thousands of products from independent vendors.')
            ->action('Start shopping', $url)
            ->line('If you have any questions, our support team is always happy to help.');
    }
}
