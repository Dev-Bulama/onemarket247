<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Which vendor_wallets balance column a ledger entry's amount applies to.
 */
enum WalletBalanceBucket: string implements HasLabel
{
    case Pending = 'pending';
    case Available = 'available';
    case Reserved = 'reserved';
    case Withdrawn = 'withdrawn';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Available => 'Available',
            self::Reserved => 'Reserved',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function column(): string
    {
        return match ($this) {
            self::Pending => 'pending_balance',
            self::Available => 'available_balance',
            self::Reserved => 'reserved_balance',
            self::Withdrawn => 'withdrawn_balance',
        };
    }
}
