<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\Settings\Pages\CreateSetting;
use App\Filament\Resources\Settings\Pages\EditSetting;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Filament\Resources\Settings\Schemas\SettingForm;
use App\Filament\Resources\Settings\Tables\SettingsTable;
use App\Models\Setting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'settings.manage';

    protected static ?string $model = Setting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Platform';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return SettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettings::route('/'),
            'create' => CreateSetting::route('/create'),
            'edit' => EditSetting::route('/{record}/edit'),
        ];
    }
}
