<?php

namespace App\Filament\Resources\Administrators;

use App\Enums\UserType;
use App\Filament\Resources\Administrators\Pages\CreateAdministrator;
use App\Filament\Resources\Administrators\Pages\EditAdministrator;
use App\Filament\Resources\Administrators\Pages\ListAdministrators;
use App\Filament\Resources\Administrators\Schemas\AdministratorForm;
use App\Filament\Resources\Administrators\Tables\AdministratorsTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shares the User model with CustomerResource, so authorization and the
 * Eloquent scope are handled directly on this Resource rather than via a
 * single shared model Policy (which couldn't distinguish "administrator
 * management" from "customer management" permissions).
 */
class AdministratorResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Administrators';

    protected static ?string $modelLabel = 'Administrator';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('user_type', [
            UserType::SuperAdmin,
            UserType::Admin,
            UserType::Staff,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return AdministratorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdministratorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdministrators::route('/'),
            'create' => CreateAdministrator::route('/create'),
            'edit' => EditAdministrator::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('admins.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny() && $record->id !== auth()->id();
    }
}
