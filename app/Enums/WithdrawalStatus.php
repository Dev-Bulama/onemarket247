<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * pending -> approved -> processing -> paid
 *    \-> rejected
 *    \-> cancelled (vendor, while still pending)
 *    \-> failed (payout attempt failed, returns to pending for retry)
 * See docs/architecture/09-lifecycles.md "Withdrawal Lifecycle". This
 * phase's actions only ever target pending/approved/paid/rejected/
 * cancelled — processing/failed are declared for schema completeness but
 * have no action reaching them yet (see the Phase 14 completion report).
 */
enum WithdrawalStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Processing = 'processing';
    case Paid = 'paid';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Processing => 'Processing',
            self::Paid => 'Paid',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Failed => 'Failed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved, self::Processing => 'info',
            self::Paid => 'success',
            self::Rejected, self::Failed => 'danger',
            self::Cancelled => 'gray',
        };
    }
}
