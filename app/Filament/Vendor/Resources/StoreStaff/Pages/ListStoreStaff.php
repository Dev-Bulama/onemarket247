<?php

namespace App\Filament\Vendor\Resources\StoreStaff\Pages;

use App\Filament\Vendor\Resources\StoreStaff\StoreStaffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStoreStaff extends ListRecords
{
    protected static string $resource = StoreStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
