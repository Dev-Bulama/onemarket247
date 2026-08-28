<?php

namespace App\Filament\Resources\EmailTemplates;

use App\Filament\Resources\EmailTemplates\Pages\EditEmailTemplate;
use App\Filament\Resources\EmailTemplates\Pages\ListEmailTemplates;
use App\Filament\Resources\EmailTemplates\Schemas\EmailTemplateForm;
use App\Filament\Resources\EmailTemplates\Tables\EmailTemplatesTable;
use App\Models\EmailTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Every row is seeded ahead of time by EmailTemplateSeeder, one per
 * App\Support\Mail\EmailTemplateKeys constant — an admin edits and
 * activates a row rather than creating arbitrary new ones, since each key
 * only does anything if a notification class actually looks it up.
 */
class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Email Templates';

    public static function form(Schema $schema): Schema
    {
        return EmailTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmailTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailTemplates::route('/'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('email_templates.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('email_templates.manage') ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
