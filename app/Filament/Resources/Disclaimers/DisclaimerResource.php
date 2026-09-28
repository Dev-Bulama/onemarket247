<?php

namespace App\Filament\Resources\Disclaimers;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\Disclaimers\Pages\CreateDisclaimer;
use App\Filament\Resources\Disclaimers\Pages\EditDisclaimer;
use App\Filament\Resources\Disclaimers\Pages\ListDisclaimers;
use App\Filament\Resources\Disclaimers\Schemas\DisclaimerForm;
use App\Filament\Resources\Disclaimers\Tables\DisclaimersTable;
use App\Models\Disclaimer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Priority 9 item 22 — admin-configurable notice/disclaimer pop-ups. See
 * App\Models\Disclaimer and App\Enums\DisclaimerTrigger for how each one
 * decides where/how often it's shown.
 */
class DisclaimerResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'disclaimers.manage';

    protected static ?string $model = Disclaimer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    public static function form(Schema $schema): Schema
    {
        return DisclaimerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DisclaimersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDisclaimers::route('/'),
            'create' => CreateDisclaimer::route('/create'),
            'edit' => EditDisclaimer::route('/{record}/edit'),
        ];
    }
}
