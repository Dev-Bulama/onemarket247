<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AttributeInputType: string implements HasLabel
{
    case Select = 'select';
    case Swatch = 'swatch';
    case Text = 'text';

    public function getLabel(): string
    {
        return match ($this) {
            self::Select => 'Dropdown',
            self::Swatch => 'Color Swatch',
            self::Text => 'Text',
        };
    }
}
