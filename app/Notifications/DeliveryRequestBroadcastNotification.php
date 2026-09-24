<?php

namespace App\Notifications;

use App\Models\DeliveryRequestNotification as DeliveryRequestNotificationModel;
use App\Notifications\Channels\AfricasTalkingChannel;
use App\Notifications\Messages\SmsMessage;
use App\Support\PriceDisplay;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerts one eligible delivery partner that a shipment needs a courier —
 * sent once per App\Models\DeliveryRequestNotification row, so each
 * partner gets their own accept link (see
 * App\Actions\Delivery\CreateDeliveryRequestAction). Push is deliberately
 * not sent here — see Priority 7's scope decision: a partner has no
 * account/app to register a device token against, so only SMS and email
 * carry the alert.
 */
class DeliveryRequestBroadcastNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly DeliveryRequestNotificationModel $notification) {}

    public function via(object $notifiable): array
    {
        return ['mail', AfricasTalkingChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->notification->deliveryRequest;
        $shipment = $request->shipment;
        $vendorOrder = $shipment->vendorOrder;
        $order = $vendorOrder->order;
        $url = route('delivery-requests.accept', $this->notification->token);

        return (new MailMessage)
            ->subject('New delivery request — '.$vendorOrder->vendor_order_number)
            ->greeting('Hello '.$this->notification->deliveryPartner->full_name.',')
            ->line('A new delivery is available for pickup.')
            ->line('Order: '.$vendorOrder->vendor_order_number)
            ->line('Deliver to: '.$order->shippingCity?->name.', '.$order->shippingState?->name)
            ->line('Earnings: '.PriceDisplay::format($request->delivery_fee))
            ->when($request->required_by, fn ($mail) => $mail->line('Required by: '.$request->required_by->format('F j, Y g:i A')))
            ->when($request->special_instructions, fn ($mail) => $mail->line('Instructions: '.$request->special_instructions))
            ->action('Accept this delivery', $url)
            ->line('This delivery goes to whichever partner accepts it first.');
    }

    public function toSms(object $notifiable): SmsMessage
    {
        $request = $this->notification->deliveryRequest;
        $url = route('delivery-requests.accept', $this->notification->token);

        return SmsMessage::create(
            $this->notification->deliveryPartner->phone,
            'OneMarket247: New delivery available (earnings '.PriceDisplay::format($request->delivery_fee)."). First to accept gets it: {$url}",
        );
    }
}
