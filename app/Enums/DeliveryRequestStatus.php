<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * pending -> accepted (first eligible partner to respond wins, see
 * App\Actions\Delivery\AcceptDeliveryRequestAction)
 *    \-> cancelled
 */
enum DeliveryRequestStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Accepted',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Accepted => 'success',
            self::Cancelled => 'danger',
        };
    }
}
