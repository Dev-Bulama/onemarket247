<?php

namespace App\Filament\Resources\VendorOrders\Tables;

use App\Enums\VendorOrderStatus;
use App\Models\VendorOrder;
use App\Support\PriceDisplay;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VendorOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('orderItems'))
            ->columns([
                TextColumn::make('vendor_order_number')
                    ->label('Sub-order')
                    ->searchable(),
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable(),
                TextColumn::make('vendor.business_name')
                    ->label('Vendor')
                    ->searchable(),
                TextColumn::make('products')
                    ->label('Products')
                    ->state(fn (VendorOrder $record) => $record->orderItems->pluck('product_name')->implode(', '))
                    ->wrap()
                    ->searchable(query: fn ($query, $search) => $query->whereHas(
                        'orderItems',
                        fn ($query) => $query->where('product_name', 'like', "%{$search}%")
                    )),
                TextColumn::make('total')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(VendorOrderStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
