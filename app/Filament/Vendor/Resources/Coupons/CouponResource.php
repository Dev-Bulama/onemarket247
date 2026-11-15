<?php

namespace App\Filament\Vendor\Resources\Coupons;

use App\Filament\Vendor\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Vendor\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Vendor\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Vendor\Resources\Coupons\Schemas\CouponForm;
use App\Filament\Vendor\Resources\Coupons\Tables\CouponsTable;
use App\Models\Coupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * A vendor's own coupons — scoped to their store only (admin's
 * platform-wide coupons, vendor_id null, never show up here). Authorization
 * comes from App\Policies\CouponPolicy, auto-resolved by Filament from the
 * model name (same pattern as VendorOrderResource) — gated by
 * store.coupons.manage, already reserved for exactly this in
 * RolePermissionSeeder even though this resource didn't exist yet.
 */
class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static ?string $navigationLabel = 'Coupons';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('vendor_id', auth()->user()?->actingVendorId());
    }

    public static function form(Schema $schema): Schema
    {
        return CouponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouponsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }
}
