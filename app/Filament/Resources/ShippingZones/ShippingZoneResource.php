<?php

namespace App\Filament\Resources\ShippingZones;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\ShippingZones\Pages\CreateShippingZone;
use App\Filament\Resources\ShippingZones\Pages\EditShippingZone;
use App\Filament\Resources\ShippingZones\Pages\ListShippingZones;
use App\Filament\Resources\ShippingZones\RelationManagers\LocationsRelationManager;
use App\Filament\Resources\ShippingZones\RelationManagers\RatesRelationManager;
use App\Filament\Resources\ShippingZones\Schemas\ShippingZoneForm;
use App\Filament\Resources\ShippingZones\Tables\ShippingZonesTable;
use App\Models\ShippingZone;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShippingZoneResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'shipping.manage';

    protected static ?string $model = ShippingZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return ShippingZoneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingZonesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LocationsRelationManager::class,
            RatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingZones::route('/'),
            'create' => CreateShippingZone::route('/create'),
            'edit' => EditShippingZone::route('/{record}/edit'),
        ];
    }
}
