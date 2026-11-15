<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Models\Order;
use App\Support\Mail\EmailTemplateKeys;
use App\Support\PriceDisplay;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent once, right after CompleteCheckoutAction creates the order (web and
 * API checkout both funnel through that one action).
 */
class OrderConfirmationNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('account.orders.show', $this->order, absolute: false));

        $template = EmailTemplate::active(EmailTemplateKeys::OrderConfirmation);

        if ($template) {
            $rendered = $template->render([
                'customer_name' => $this->order->customerName(),
                'order_number' => (string) $this->order->id,
                'order_total' => PriceDisplay::format($this->order->total),
                'order_url' => $url,
            ]);

            return (new MailMessage)
                ->subject($rendered['subject'])
                ->line($rendered['body'])
                ->action('View your order', $url);
        }

        return (new MailMessage)
            ->subject('Your OneMarket247 order #'.$this->order->id.' is confirmed')
            ->greeting('Thanks for your order, '.$this->order->customerName().'!')
            ->line('We\'ve received order #'.$this->order->id.' for a total of '.PriceDisplay::format($this->order->total).'.')
            ->action('View your order', $url)
            ->line('We\'ll let you know as soon as it ships.');
    }
}
