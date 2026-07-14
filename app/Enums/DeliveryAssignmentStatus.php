<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * assigned -> picked_up -> in_transit -> delivered
 *    \-> failed
 */
enum DeliveryAssignmentStatus: string implements HasColor, HasLabel
{
    case Assigned = 'assigned';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::PickedUp => 'Picked up',
            self::InTransit => 'In transit',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Assigned => 'gray',
            self::PickedUp, self::InTransit => 'info',
            self::Delivered => 'success',
            self::Failed => 'danger',
        };
    }
}
