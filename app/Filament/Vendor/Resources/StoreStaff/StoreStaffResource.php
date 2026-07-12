<?php

namespace App\Filament\Vendor\Resources\StoreStaff;

use App\Filament\Vendor\Resources\StoreStaff\Pages\CreateStoreStaff;
use App\Filament\Vendor\Resources\StoreStaff\Pages\EditStoreStaff;
use App\Filament\Vendor\Resources\StoreStaff\Pages\ListStoreStaff;
use App\Filament\Vendor\Resources\StoreStaff\Schemas\StoreStaffForm;
use App\Filament\Vendor\Resources\StoreStaff\Tables\StoreStaffTable;
use App\Models\StoreStaff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Authorization is delegated to StoreStaffPolicy (owner-only regardless of
 * granted permissions — see docs/architecture/07-vendor-dashboard.md §3).
 */
class StoreStaffResource extends Resource
{
    protected static ?string $model = StoreStaff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $vendorId = Auth::guard('vendor')->user()->actingVendorId();

        return parent::getEloquentQuery()->whereHas('store', fn (Builder $query) => $query->where('vendor_id', $vendorId));
    }

    public static function form(Schema $schema): Schema
    {
        return StoreStaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StoreStaffTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStoreStaff::route('/'),
            'create' => CreateStoreStaff::route('/create'),
            'edit' => EditStoreStaff::route('/{record}/edit'),
        ];
    }
}
