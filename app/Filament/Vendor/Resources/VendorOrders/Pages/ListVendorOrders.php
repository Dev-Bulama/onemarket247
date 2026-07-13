<?php

namespace App\Filament\Vendor\Resources\VendorOrders\Pages;

use App\Filament\Vendor\Resources\VendorOrders\VendorOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorOrders extends ListRecords
{
    protected static string $resource = VendorOrderResource::class;
}
