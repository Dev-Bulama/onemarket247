<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AgentDocumentType: string implements HasLabel
{
    case Identity = 'identity';
    case ProofOfAddress = 'proof_of_address';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Identity => 'Identity Document',
            self::ProofOfAddress => 'Proof of Address',
            self::Other => 'Other',
        };
    }
}
