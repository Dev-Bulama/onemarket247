<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum BillingPeriod: string implements HasLabel
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Lifetime = 'lifetime';

    public function getLabel(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
            self::Lifetime => 'Lifetime',
        };
    }

    public function durationInDays(): ?int
    {
        return match ($this) {
            self::Monthly => 30,
            self::Yearly => 365,
            self::Lifetime => null,
        };
    }
}
