<?php

namespace App\Filament\Resources\LegalPages;

use App\Filament\Resources\LegalPages\Pages\EditLegalPage;
use App\Filament\Resources\LegalPages\Pages\ListLegalPages;
use App\Filament\Resources\LegalPages\Schemas\LegalPageForm;
use App\Filament\Resources\LegalPages\Tables\LegalPagesTable;
use App\Models\LegalPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The Terms of Service and Privacy Policy — both rows are seeded once by
 * LegalPageSeeder; an admin edits them rather than creating arbitrary new
 * ones, since App\Support\LegalPageKeys and the storefront/mobile routes
 * only ever look up these two fixed keys.
 */
class LegalPageResource extends Resource
{
    protected static ?string $model = LegalPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Legal Pages';

    public static function form(Schema $schema): Schema
    {
        return LegalPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegalPagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegalPages::route('/'),
            'edit' => EditLegalPage::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('cms.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('cms.manage') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
