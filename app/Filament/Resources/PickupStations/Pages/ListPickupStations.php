<?php

namespace App\Filament\Resources\PickupStations\Pages;

use App\Filament\Resources\PickupStations\PickupStationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPickupStations extends ListRecords
{
    protected static string $resource = PickupStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
