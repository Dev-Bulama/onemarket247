<?php

namespace App\Filament\Resources\ProductSpotlights\Pages;

use App\Filament\Resources\ProductSpotlights\ProductSpotlightResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductSpotlights extends ListRecords
{
    protected static string $resource = ProductSpotlightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
