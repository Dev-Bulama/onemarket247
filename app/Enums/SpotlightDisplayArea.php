<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SpotlightDisplayArea: string implements HasColor, HasLabel
{
    case Homepage = 'homepage';
    case Featured = 'featured';
    case Category = 'category';
    case Banner = 'banner';
    case Search = 'search';

    public function getLabel(): string
    {
        return match ($this) {
            self::Homepage => 'Homepage',
            self::Featured => 'Featured Products section',
            self::Category => 'Category pages',
            self::Banner => 'Promotional banners',
            self::Search => 'Search / discovery areas',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Homepage => 'info',
            self::Featured => 'success',
            self::Category => 'warning',
            self::Banner => 'primary',
            self::Search => 'gray',
        };
    }
}
