<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case PendingPayment = 'pending_payment';
    case PaymentProcessing = 'payment_processing';
    case Paid = 'paid';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case PartiallyDelivered = 'partially_delivered';
    case Delivered = 'delivered';
    case Completed = 'completed';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case Returned = 'returned';
    case PartiallyReturned = 'partially_returned';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Disputed = 'disputed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingPayment => 'Pending Payment',
            self::PaymentProcessing => 'Payment Processing',
            self::Paid => 'Paid',
            self::Confirmed => 'Confirmed',
            self::Processing => 'Processing',
            self::PartiallyDelivered => 'Partially Delivered',
            self::Delivered => 'Delivered',
            self::Completed => 'Completed',
            self::OnHold => 'On Hold',
            self::Cancelled => 'Cancelled',
            self::Failed => 'Failed',
            self::Returned => 'Returned',
            self::PartiallyReturned => 'Partially Returned',
            self::Refunded => 'Refunded',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Disputed => 'Disputed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingPayment, self::PaymentProcessing => 'warning',
            self::Paid, self::Confirmed, self::Processing, self::PartiallyDelivered => 'info',
            self::Delivered, self::Completed => 'success',
            self::OnHold, self::Disputed => 'warning',
            self::Cancelled, self::Failed => 'danger',
            self::Returned, self::PartiallyReturned, self::Refunded, self::PartiallyRefunded => 'gray',
        };
    }
}
