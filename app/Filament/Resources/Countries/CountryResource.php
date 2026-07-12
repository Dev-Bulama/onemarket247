<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\Countries\Pages\CreateCountry;
use App\Filament\Resources\Countries\Pages\EditCountry;
use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Filament\Resources\Countries\Schemas\CountryForm;
use App\Filament\Resources\Countries\Tables\CountriesTable;
use App\Models\Country;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CountryResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'settings.manage';

    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return CountryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CountriesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            'create' => CreateCountry::route('/create'),
            'edit' => EditCountry::route('/{record}/edit'),
        ];
    }
}
