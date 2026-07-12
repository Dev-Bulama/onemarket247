<?php

namespace App\Filament\Resources\VendorSubscriptionPlans\Pages;

use App\Filament\Resources\VendorSubscriptionPlans\VendorSubscriptionPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorSubscriptionPlans extends ListRecords
{
    protected static string $resource = VendorSubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
