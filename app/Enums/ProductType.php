<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductType: string implements HasLabel
{
    case Simple = 'simple';
    case Variable = 'variable';
    case Digital = 'digital';

    public function getLabel(): string
    {
        return match ($this) {
            self::Simple => 'Simple',
            self::Variable => 'Variable',
            self::Digital => 'Digital',
        };
    }

    public function hasVariations(): bool
    {
        return $this === self::Variable;
    }
}
