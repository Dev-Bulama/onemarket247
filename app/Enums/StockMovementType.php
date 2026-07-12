<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StockMovementType: string implements HasColor, HasLabel
{
    case Adjustment = 'adjustment';
    case Reservation = 'reservation';
    case Release = 'release';
    case Deduction = 'deduction';
    case Restoration = 'restoration';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case TransferCancelled = 'transfer_cancelled';
    case DamageReported = 'damage_reported';

    public function getLabel(): string
    {
        return match ($this) {
            self::Adjustment => 'Adjustment',
            self::Reservation => 'Reservation',
            self::Release => 'Release',
            self::Deduction => 'Deduction',
            self::Restoration => 'Restoration',
            self::TransferOut => 'Transfer Out',
            self::TransferIn => 'Transfer In',
            self::TransferCancelled => 'Transfer Cancelled',
            self::DamageReported => 'Damage Reported',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Adjustment => 'gray',
            self::Reservation => 'warning',
            self::Release => 'gray',
            self::Deduction => 'danger',
            self::Restoration => 'success',
            self::TransferOut => 'info',
            self::TransferIn => 'success',
            self::TransferCancelled => 'gray',
            self::DamageReported => 'danger',
        };
    }
}
