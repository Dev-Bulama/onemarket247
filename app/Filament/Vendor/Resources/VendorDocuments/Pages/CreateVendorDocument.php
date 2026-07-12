<?php

namespace App\Filament\Vendor\Resources\VendorDocuments\Pages;

use App\Filament\Vendor\Resources\VendorDocuments\VendorDocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateVendorDocument extends CreateRecord
{
    protected static string $resource = VendorDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['vendor_id'] = Auth::guard('vendor')->user()->actingVendorId();

        return $data;
    }
}
