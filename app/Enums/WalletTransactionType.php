<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Ledger entry types — see docs/architecture/09-lifecycles.md "Vendor
 * Wallet Lifecycle".
 */
enum WalletTransactionType: string implements HasColor, HasLabel
{
    case SaleCreditPending = 'sale_credit_pending';
    case SaleCreditAvailable = 'sale_credit_available';
    case RefundDebit = 'refund_debit';
    case Adjustment = 'adjustment';
    case WithdrawalHold = 'withdrawal_hold';
    case WithdrawalPaid = 'withdrawal_paid';
    case WithdrawalReversed = 'withdrawal_reversed';

    public function getLabel(): string
    {
        return match ($this) {
            self::SaleCreditPending => 'Sale credit (pending)',
            self::SaleCreditAvailable => 'Sale credit (available)',
            self::RefundDebit => 'Refund debit',
            self::Adjustment => 'Manual adjustment',
            self::WithdrawalHold => 'Withdrawal hold',
            self::WithdrawalPaid => 'Withdrawal paid',
            self::WithdrawalReversed => 'Withdrawal reversed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SaleCreditPending => 'warning',
            self::SaleCreditAvailable => 'success',
            self::RefundDebit => 'danger',
            self::Adjustment => 'gray',
            self::WithdrawalHold => 'info',
            self::WithdrawalPaid => 'success',
            self::WithdrawalReversed => 'warning',
        };
    }
}
