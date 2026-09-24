<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeliveryPartnerStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Deactivated => 'Deactivated',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Suspended => 'warning',
            self::Deactivated => 'danger',
        };
    }

    /**
     * Whether this partner should be eligible to receive delivery-request
     * alerts — only a currently active, non suspended/deactivated partner
     * counts (mirrors AgentStatus::isActive()).
     */
    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
