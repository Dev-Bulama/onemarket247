<?php

namespace App\Filament\Resources\Disclaimers\Pages;

use App\Filament\Resources\Disclaimers\DisclaimerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDisclaimers extends ListRecords
{
    protected static string $resource = DisclaimerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
