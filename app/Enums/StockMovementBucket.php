<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StockMovementBucket: string implements HasLabel
{
    case OnHand = 'on_hand';
    case Reserved = 'reserved';
    case Damaged = 'damaged';
    case Incoming = 'incoming';

    public function getLabel(): string
    {
        return match ($this) {
            self::OnHand => 'On Hand',
            self::Reserved => 'Reserved',
            self::Damaged => 'Damaged',
            self::Incoming => 'Incoming',
        };
    }
}
