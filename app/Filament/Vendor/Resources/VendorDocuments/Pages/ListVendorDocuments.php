<?php

namespace App\Filament\Vendor\Resources\VendorDocuments\Pages;

use App\Filament\Vendor\Resources\VendorDocuments\VendorDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVendorDocuments extends ListRecords
{
    protected static string $resource = VendorDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
