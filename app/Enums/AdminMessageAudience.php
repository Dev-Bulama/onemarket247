<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AdminMessageAudience: string implements HasLabel
{
    case AllUsers = 'all_users';
    case AllCustomers = 'all_customers';
    case AllVendors = 'all_vendors';
    case Specific = 'specific';

    public function getLabel(): string
    {
        return match ($this) {
            self::AllUsers => 'All users (customers + vendors)',
            self::AllCustomers => 'All customers',
            self::AllVendors => 'All vendors',
            self::Specific => 'Specific people',
        };
    }
}
