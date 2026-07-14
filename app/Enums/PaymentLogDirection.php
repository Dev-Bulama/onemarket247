<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentLogDirection: string implements HasColor, HasLabel
{
    case Request = 'request';
    case Response = 'response';
    case Webhook = 'webhook';
    case Error = 'error';

    public function getLabel(): string
    {
        return match ($this) {
            self::Request => 'Outbound request',
            self::Response => 'Gateway response',
            self::Webhook => 'Webhook received',
            self::Error => 'Error',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Request => 'gray',
            self::Response => 'info',
            self::Webhook => 'success',
            self::Error => 'danger',
        };
    }
}
