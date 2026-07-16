<?php

namespace App\Filament\Vendor\Resources\VendorOrders;

use App\Filament\Vendor\Resources\VendorOrders\Pages\ListVendorOrders;
use App\Filament\Vendor\Resources\VendorOrders\Pages\ViewVendorOrder;
use App\Filament\Vendor\Resources\VendorOrders\RelationManagers\OrderItemsRelationManager;
use App\Filament\Vendor\Resources\VendorOrders\RelationManagers\StatusHistoriesRelationManager;
use App\Filament\Vendor\Resources\VendorOrders\Tables\VendorOrdersTable;
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
 * BelongsToVendorScope (registered on the VendorOrder model) automatically
 * restricts every query here to the authenticated vendor guard user's own
 * vendor_orders, so no extra scoping is needed on top of that. Access
 * control itself comes from App\Policies\VendorOrderPolicy, auto-resolved
 * by Filament from the model name — viewAny()/view() gate the owner or an
 * active store.orders.manage staff member in; update() (used to gate the
 * fulfilment/cancel actions on the view page) additionally requires
 * store.orders.fulfil for staff. Read-only + action-driven, matching the
 * admin oversight resource: orders are placed via checkout and mutated
 * only through App\Actions\Order\*, never a free-form edit form.
 */
class VendorOrderResource extends Resource
{
    protected static ?string $model = VendorOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Orders';

    public static function table(Table $table): Table
    {
        return VendorOrdersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')
                ->columns(3)
                ->schema([
                    TextEntry::make('vendor_order_number')->label('Sub-order'),
                    TextEntry::make('order.order_number')->label('Order'),
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
