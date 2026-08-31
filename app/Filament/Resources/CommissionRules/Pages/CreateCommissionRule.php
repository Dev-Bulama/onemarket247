<?php

namespace App\Filament\Resources\CommissionRules\Pages;

use App\Filament\Resources\CommissionRules\CommissionRuleResource;
use App\Support\AuditLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateCommissionRule extends CreateRecord
{
    protected static string $resource = CommissionRuleResource::class;

    protected function afterCreate(): void
    {
        AuditLogger::record('commission_rule.created', $this->record, null, $this->record->toArray());
    }
}
