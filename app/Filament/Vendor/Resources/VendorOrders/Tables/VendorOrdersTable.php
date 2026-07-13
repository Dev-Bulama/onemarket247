<?php

namespace App\Filament\Vendor\Resources\VendorOrders\Tables;

use App\Enums\VendorOrderStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VendorOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vendor_order_number')
                    ->label('Order')
                    ->searchable(),
                TextColumn::make('order_items_count')
                    ->label('Items')
                    ->counts('orderItems'),
                TextColumn::make('total')
                    ->money('USD', divideBy: 100)
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
