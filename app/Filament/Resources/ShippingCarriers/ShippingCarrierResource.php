<?php

namespace App\Filament\Resources\ShippingCarriers;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\ShippingCarriers\Pages\CreateShippingCarrier;
use App\Filament\Resources\ShippingCarriers\Pages\EditShippingCarrier;
use App\Filament\Resources\ShippingCarriers\Pages\ListShippingCarriers;
use App\Filament\Resources\ShippingCarriers\Schemas\ShippingCarrierForm;
use App\Filament\Resources\ShippingCarriers\Tables\ShippingCarriersTable;
use App\Models\ShippingCarrier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShippingCarrierResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'shipping.manage';

    protected static ?string $model = ShippingCarrier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ShippingCarrierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingCarriersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingCarriers::route('/'),
            'create' => CreateShippingCarrier::route('/create'),
            'edit' => EditShippingCarrier::route('/{record}/edit'),
        ];
    }
}
