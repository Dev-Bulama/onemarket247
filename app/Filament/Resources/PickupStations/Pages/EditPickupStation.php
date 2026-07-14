<?php

namespace App\Filament\Resources\PickupStations\Pages;

use App\Filament\Resources\PickupStations\PickupStationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPickupStation extends EditRecord
{
    protected static string $resource = PickupStationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
