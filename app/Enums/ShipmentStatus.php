<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * pending -> packed -> shipped -> in_transit -> out_for_delivery -> delivered
 *    \-> failed
 *    \-> returned
 * Each transition appends a ShipmentEvent and, where the corresponding
 * VendorOrderStatus exists (shipped/out_for_delivery/delivered), also
 * advances the parent vendor order via UpdateVendorOrderStatusAction — see
 * docs/reports/phase-15-completion-report.md.
 */
enum ShipmentStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Returned = 'returned';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Packed => 'Packed',
            self::Shipped => 'Shipped',
            self::InTransit => 'In transit',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::Failed => 'Failed',
            self::Returned => 'Returned',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending, self::Packed => 'gray',
            self::Shipped, self::InTransit, self::OutForDelivery => 'info',
            self::Delivered => 'success',
            self::Failed, self::Returned => 'danger',
        };
    }
}
