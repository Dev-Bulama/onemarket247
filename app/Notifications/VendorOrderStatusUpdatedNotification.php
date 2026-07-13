<?php

namespace App\Notifications;

use App\Models\VendorOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorOrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly VendorOrder $vendorOrder) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->vendorOrder->order;

        return (new MailMessage)
            ->subject('Update on your order '.$order->order_number)
            ->greeting('Hello '.$order->customerName().',')
            ->line('Part of your order, '.$this->vendorOrder->vendor_order_number.', is now: '.$this->vendorOrder->status->getLabel().'.')
            ->action('View your order', route('checkout.confirmation', $order));
    }
}
