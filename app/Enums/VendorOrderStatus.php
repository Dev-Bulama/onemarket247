<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VendorOrderStatus: string implements HasColor, HasLabel
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case ReadyForPickup = 'ready_for_pickup';
    case Shipped = 'shipped';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';
    case Returned = 'returned';
    case Refunded = 'refunded';
    case Disputed = 'disputed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::ReadyForPickup => 'Ready for Pickup',
            self::Shipped => 'Shipped',
            self::OutForDelivery => 'Out for Delivery',
            self::Delivered => 'Delivered',
            self::Completed => 'Completed',
            self::OnHold => 'On Hold',
            self::Cancelled => 'Cancelled',
            self::Returned => 'Returned',
            self::Refunded => 'Refunded',
            self::Disputed => 'Disputed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingPayment => 'warning',
            self::Confirmed, self::Processing, self::ReadyForPickup, self::Shipped, self::OutForDelivery => 'info',
            self::Delivered, self::Completed => 'success',
            self::OnHold, self::Disputed => 'warning',
            self::Cancelled => 'danger',
            self::Returned, self::Refunded => 'gray',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled, self::Returned, self::Refunded], true);
    }
}
