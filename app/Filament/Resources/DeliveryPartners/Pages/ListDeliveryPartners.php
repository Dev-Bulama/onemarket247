<?php

namespace App\Filament\Resources\DeliveryPartners\Pages;

use App\Filament\Resources\DeliveryPartners\DeliveryPartnerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeliveryPartners extends ListRecords
{
    protected static string $resource = DeliveryPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
