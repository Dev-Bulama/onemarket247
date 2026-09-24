<?php

namespace App\Filament\Resources\DeliveryPartners\Pages;

use App\Filament\Resources\DeliveryPartners\DeliveryPartnerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDeliveryPartner extends EditRecord
{
    protected static string $resource = DeliveryPartnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
