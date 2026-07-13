<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CartStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Merged = 'merged';
    case Abandoned = 'abandoned';
    case Converted = 'converted';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Merged => 'Merged',
            self::Abandoned => 'Abandoned',
            self::Converted => 'Converted',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Merged => 'gray',
            self::Abandoned => 'warning',
            self::Converted => 'info',
        };
    }
}
