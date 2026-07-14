<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum WithdrawalMethodType: string implements HasLabel
{
    case BankTransfer = 'bank_transfer';

    public function getLabel(): string
    {
        return match ($this) {
            self::BankTransfer => 'Bank transfer',
        };
    }
}
