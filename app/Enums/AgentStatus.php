<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AgentStatus: string implements HasColor, HasLabel
{
    case Approved = 'approved';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public function getLabel(): string
    {
        return match ($this) {
            self::Approved => 'Approved',
            self::Suspended => 'Suspended',
            self::Deactivated => 'Deactivated',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Approved => 'success',
            self::Suspended => 'warning',
            self::Deactivated => 'danger',
        };
    }

    /**
     * Whether a vendor should be able to pick this agent from the
     * "Registered Agent" dropdown — only a currently approved, non
     * suspended/deactivated agent counts as active.
     */
    public function isActive(): bool
    {
        return $this === self::Approved;
    }
}
