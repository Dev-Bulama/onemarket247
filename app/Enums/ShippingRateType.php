<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ShippingRateType: string implements HasLabel
{
    case Flat = 'flat';
    case PerWeight = 'per_weight';
    case Free = 'free';

    public function getLabel(): string
    {
        return match ($this) {
            self::Flat => 'Flat rate',
            self::PerWeight => 'Per kilogram',
            self::Free => 'Free shipping',
        };
    }
}
