<?php

namespace App\Filament\Resources\Administrators\Pages;

use App\Filament\Resources\Administrators\AdministratorResource;
use App\Support\AuditLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdministrator extends EditRecord
{
    protected static string $resource = AdministratorResource::class;

    /** @var array<int, string> */
    private array $rolesBeforeSave = [];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->record->id !== auth()->id()),
        ];
    }

    protected function beforeSave(): void
    {
        $this->rolesBeforeSave = $this->record->roles()->pluck('name')->all();
    }

    /**
     * Role assignment is a Filament relationship field (see
     * AdministratorForm), synced separately from the record's own
     * update() — not visible in $record->getChanges() — so this compares
     * the roles list explicitly instead.
     */
    protected function afterSave(): void
    {
        $rolesAfterSave = $this->record->fresh()->roles()->pluck('name')->all();

        if ($this->rolesBeforeSave !== $rolesAfterSave) {
            AuditLogger::record(
                'administrator.roles_updated',
                $this->record,
                ['roles' => $this->rolesBeforeSave],
                ['roles' => $rolesAfterSave],
            );
        }
    }
}
