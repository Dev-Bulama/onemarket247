<?php

namespace App\Filament\Resources\Disclaimers\Pages;

use App\Filament\Resources\Disclaimers\DisclaimerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDisclaimer extends EditRecord
{
    protected static string $resource = DisclaimerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
