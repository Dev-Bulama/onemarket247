<?php

namespace App\Filament\Resources\ShippingClasses;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\ShippingClasses\Pages\CreateShippingClass;
use App\Filament\Resources\ShippingClasses\Pages\EditShippingClass;
use App\Filament\Resources\ShippingClasses\Pages\ListShippingClasses;
use App\Filament\Resources\ShippingClasses\Schemas\ShippingClassForm;
use App\Filament\Resources\ShippingClasses\Tables\ShippingClassesTable;
use App\Models\ShippingClass;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShippingClassResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'shipping.manage';

    protected static ?string $model = ShippingClass::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Shipping';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ShippingClassForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingClassesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingClasses::route('/'),
            'create' => CreateShippingClass::route('/create'),
            'edit' => EditShippingClass::route('/{record}/edit'),
        ];
    }
}
