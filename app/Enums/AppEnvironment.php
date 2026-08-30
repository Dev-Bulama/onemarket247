<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AppEnvironment: string implements HasLabel
{
    case Local = 'local';
    case Production = 'production';

    public function getLabel(): string
    {
        return match ($this) {
            self::Local => 'Local (development)',
            self::Production => 'Production',
        };
    }
}
