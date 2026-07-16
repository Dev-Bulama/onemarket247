<?php

namespace App\Filament\Resources\VendorOrders;

use App\Filament\Resources\VendorOrders\Pages\ListVendorOrders;
use App\Filament\Resources\VendorOrders\Pages\ViewVendorOrder;
use App\Filament\Resources\VendorOrders\RelationManagers\OrderItemsRelationManager;
use App\Filament\Resources\VendorOrders\RelationManagers\ShipmentsRelationManager;
use App\Filament\Resources\VendorOrders\RelationManagers\StatusHistoriesRelationManager;
use App\Filament\Resources\VendorOrders\Tables\VendorOrdersTable;
use App\Models\VendorOrder;
use App\Support\PriceDisplay;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only + action-driven, like StockTransferResource: a vendor order's
 * status only ever moves through App\Actions\Order\{UpdateVendorOrderStatusAction,
 * CancelVendorOrderAction}, both invoked from ViewVendorOrder's header
 * actions, never via a free-form edit form.
 */
class VendorOrderResource extends Resource
{
    protected static ?string $model = VendorOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static string|\UnitEnum|null $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Vendor Orders';

    public static function table(Table $table): Table
    {
        return VendorOrdersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Vendor order')
                ->columns(3)
                ->schema([
                    TextEntry::make('vendor_order_number')->label('Sub-order'),
                    TextEntry::make('order.order_number')->label('Order'),
                    TextEntry::make('vendor.business_name')->label('Vendor'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('created_at')->dateTime(),
                ]),
            Section::make('Totals')
                ->columns(4)
                ->schema([
                    TextEntry::make('subtotal')->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                    TextEntry::make('discount_amount')->label('Discount')->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                    TextEntry::make('shipping_amount')->label('Shipping')->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                    TextEntry::make('total')->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            OrderItemsRelationManager::class,
            ShipmentsRelationManager::class,
            StatusHistoriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendorOrders::route('/'),
            'view' => ViewVendorOrder::route('/{record}'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
