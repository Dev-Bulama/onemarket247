<?php

namespace App\Filament\Resources\VendorSubscriptionPlans\Pages;

use App\Filament\Resources\VendorSubscriptionPlans\VendorSubscriptionPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVendorSubscriptionPlan extends EditRecord
{
    protected static string $resource = VendorSubscriptionPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
