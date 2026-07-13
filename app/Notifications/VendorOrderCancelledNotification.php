<?php

namespace App\Notifications;

use App\Models\VendorOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorOrderCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly VendorOrder $vendorOrder, private readonly string $reason) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->vendorOrder->order;

        return (new MailMessage)
            ->subject('Part of your order '.$order->order_number.' was cancelled')
            ->greeting('Hello '.$order->customerName().',')
            ->line('Part of your order, '.$this->vendorOrder->vendor_order_number.', has been cancelled.')
            ->line('Reason: '.$this->reason)
            ->action('View your order', route('checkout.confirmation', $order));
    }
}
