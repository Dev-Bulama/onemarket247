<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderNoteVisibility: string implements HasColor, HasLabel
{
    case Customer = 'customer';
    case Vendor = 'vendor';
    case Internal = 'internal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Customer => 'Visible to customer',
            self::Vendor => 'Visible to vendor',
            self::Internal => 'Internal only',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Customer => 'success',
            self::Vendor => 'info',
            self::Internal => 'gray',
        };
    }
}
