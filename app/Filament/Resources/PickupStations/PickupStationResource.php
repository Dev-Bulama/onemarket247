<?php

namespace App\Filament\Resources\PickupStations;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\PickupStations\Pages\CreatePickupStation;
use App\Filament\Resources\PickupStations\Pages\EditPickupStation;
use App\Filament\Resources\PickupStations\Pages\ListPickupStations;
use App\Filament\Resources\PickupStations\Schemas\PickupStationForm;
use App\Filament\Resources\PickupStations\Tables\PickupStationsTable;
use App\Models\PickupStation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PickupStationResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'shipping.manage';

    protected static ?string $model = PickupStation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return PickupStationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PickupStationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPickupStations::route('/'),
            'create' => CreatePickupStation::route('/create'),
            'edit' => EditPickupStation::route('/{record}/edit'),
        ];
    }
}
