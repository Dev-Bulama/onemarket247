<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Product-level low-stock alerts (stock_quantity <= low_stock_threshold,
 * manage_stock enabled). Variation-level stock for variable products
 * shares the same threshold but is surfaced in the vendor inventory page's
 * stock table instead of duplicated here, since a single cross-model table
 * widget can't cleanly union Product and ProductVariation rows.
 */
class LowStockWidget extends TableWidget
{
    protected static ?string $heading = 'Low Stock Products';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Product::query()
                ->where('manage_stock', true)
                ->whereNotNull('low_stock_threshold')
                ->whereColumn('stock_quantity', '<=', 'low_stock_threshold'))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('vendor.business_name')
                    ->label('Vendor'),
                TextColumn::make('stock_quantity')
                    ->label('In stock')
                    ->numeric(),
                TextColumn::make('low_stock_threshold')
                    ->label('Threshold')
                    ->numeric(),
                TextColumn::make('stock_status')
                    ->badge(),
            ])
            ->defaultSort('stock_quantity')
            ->paginated([5, 10, 25]);
    }
}
