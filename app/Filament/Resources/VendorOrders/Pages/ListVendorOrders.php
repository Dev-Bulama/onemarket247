<?php

namespace App\Filament\Resources\VendorOrders\Pages;

use App\Filament\Resources\VendorOrders\VendorOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListVendorOrders extends ListRecords
{
    protected static string $resource = VendorOrderResource::class;
}
