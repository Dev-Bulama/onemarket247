<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserType: string implements HasLabel
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Staff = 'staff';
    case VendorOwner = 'vendor_owner';
    case VendorStaff = 'vendor_staff';
    case Customer = 'customer';
    case Delivery = 'delivery';
    case Affiliate = 'affiliate';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Administrator',
            self::Admin => 'Administrator',
            self::Staff => 'Staff',
            self::VendorOwner => 'Vendor Owner',
            self::VendorStaff => 'Vendor Staff',
            self::Customer => 'Customer',
            self::Delivery => 'Delivery Personnel',
            self::Affiliate => 'Affiliate',
        };
    }
}
