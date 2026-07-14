<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DeliveryEvidenceType: string implements HasLabel
{
    case Signature = 'signature';
    case Photo = 'photo';

    public function getLabel(): string
    {
        return match ($this) {
            self::Signature => 'Signature',
            self::Photo => 'Photo',
        };
    }
}
