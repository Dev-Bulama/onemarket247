<?php

namespace App\Filament\Resources\VendorOrders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only — items are snapshotted at checkout time by
 * App\Actions\Checkout\CompleteCheckoutAction and never edited afterward.
 */
class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'orderItems';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')
                    ->label('Product'),
                TextColumn::make('sku'),
                TextColumn::make('warehouse.name')
                    ->placeholder('—'),
                TextColumn::make('unit_price')
                    ->money('USD', divideBy: 100),
                TextColumn::make('quantity')
                    ->numeric(),
                TextColumn::make('line_total')
                    ->money('USD', divideBy: 100),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
