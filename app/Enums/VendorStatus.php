<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum VendorStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';
    case Banned = 'banned';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
            self::Deactivated => 'Deactivated',
            self::Banned => 'Banned',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft, self::Pending, self::UnderReview => 'warning',
            self::Approved => 'success',
            self::Rejected, self::Suspended, self::Deactivated, self::Banned => 'danger',
        };
    }

    /**
     * Whether a vendor in this status may authenticate into the vendor
     * dashboard/API at all (see docs/architecture/07-vendor-dashboard.md §3).
     */
    public function allowsDashboardAccess(): bool
    {
        return $this === self::Approved;
    }
}
