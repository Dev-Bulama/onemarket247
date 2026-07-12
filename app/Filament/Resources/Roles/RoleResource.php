<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

/**
 * Scoped to admin-guard roles only; vendor-guard role management belongs to
 * the vendor dashboard build-out in Phase 5.
 */
class RoleResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'roles.manage';

    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|\UnitEnum|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('guard_name', 'admin');
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
