<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum VendorDocumentType: string implements HasLabel
{
    case Identity = 'identity';
    case BusinessRegistration = 'business_registration';
    case TaxCertificate = 'tax_certificate';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Identity => 'Identity Document',
            self::BusinessRegistration => 'Business Registration',
            self::TaxCertificate => 'Tax Certificate',
            self::Other => 'Other',
        };
    }
}
