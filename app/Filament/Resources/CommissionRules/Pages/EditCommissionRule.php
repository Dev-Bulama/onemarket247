<?php

namespace App\Filament\Resources\CommissionRules\Pages;

use App\Filament\Resources\CommissionRules\CommissionRuleResource;
use App\Support\AuditLogger;
use Filament\Resources\Pages\EditRecord;

class EditCommissionRule extends EditRecord
{
    protected static string $resource = CommissionRuleResource::class;

    protected function afterSave(): void
    {
        $changes = collect($this->record->getChanges())->except('updated_at');

        if ($changes->isEmpty()) {
            return;
        }

        $before = $changes->keys()->mapWithKeys(fn (string $key) => [$key => $this->record->getOriginal($key)]);

        AuditLogger::record('commission_rule.updated', $this->record, $before->all(), $changes->all());
    }
}
