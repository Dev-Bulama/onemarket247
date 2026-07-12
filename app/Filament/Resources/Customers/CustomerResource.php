<?php

namespace App\Filament\Resources\Customers;

use App\Enums\UserType;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shares the User model with AdministratorResource — see that class for why
 * authorization/scoping live directly on the Resource. Customers are never
 * created from this panel (they self-register via the storefront); this
 * resource is deliberately view/edit/suspend only.
 */
class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Customers';

    protected static ?string $modelLabel = 'Customer';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_type', UserType::Customer);
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('customers.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('customers.manage') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('customers.manage') ?? false;
    }
}
