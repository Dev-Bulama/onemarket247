<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/**
 * Resolution order (most to least specific) — see
 * docs/architecture/09-lifecycles.md "Commission Lifecycle".
 */
enum CommissionScopeType: string implements HasLabel
{
    case Product = 'product';
    case Category = 'category';
    case Vendor = 'vendor';
    case SubscriptionPlan = 'subscription_plan';
    case Global = 'global';

    public function getLabel(): string
    {
        return match ($this) {
            self::Product => 'Product',
            self::Category => 'Category',
            self::Vendor => 'Vendor',
            self::SubscriptionPlan => 'Subscription Plan',
            self::Global => 'Global (default)',
        };
    }
}
