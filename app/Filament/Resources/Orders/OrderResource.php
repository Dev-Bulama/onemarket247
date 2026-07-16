<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Orders\RelationManagers\VendorOrdersRelationManager;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Support\PriceDisplay;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Read-only + action-driven, like StockTransferResource: orders are placed
 * via checkout and mutated only through the lifecycle actions
 * (App\Actions\Order\*), never created or free-form edited here. This
 * resource is the platform-wide oversight view; per-vendor fulfilment
 * actions live on VendorOrderResource instead, since status belongs to the
 * child VendorOrder, not the aggregated parent (see OrderStatusAggregator).
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string|\UnitEnum|null $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return OrdersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')
                ->columns(3)
                ->schema([
                    TextEntry::make('order_number'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('placed_at')->dateTime(),
                    TextEntry::make('customerName')->label('Customer')->state(fn (Order $record) => $record->customerName()),
                    TextEntry::make('customerEmail')->label('Email')->state(fn (Order $record) => $record->customerEmail()),
                    TextEntry::make('coupon_code')->label('Coupon')->placeholder('—'),
                ]),
            Section::make('Shipping address')
                ->columns(2)
                ->schema([
                    TextEntry::make('shipping_full_name')->label('Name'),
                    TextEntry::make('shipping_phone')->label('Phone')->placeholder('—'),
                    TextEntry::make('shipping_address_line_1')->label('Address')->columnSpanFull(),
                    TextEntry::make('shipping_address_line_2')->label(' ')->placeholder('')->columnSpanFull(),
                    TextEntry::make('shippingCity.name')->label('City')->placeholder('—'),
                    TextEntry::make('shippingState.name')->label('State')->placeholder('—'),
                    TextEntry::make('shippingCountry.name')->label('Country')->placeholder('—'),
                    TextEntry::make('shipping_postal_code')->label('Postal code')->placeholder('—'),
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
            VendorOrdersRelationManager::class,
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
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
