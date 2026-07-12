<?php

namespace App\Filament\Resources\Administrators\Pages;

use App\Filament\Resources\Administrators\AdministratorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdministrator extends EditRecord
{
    protected static string $resource = AdministratorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => $this->record->id !== auth()->id()),
        ];
    }
}
