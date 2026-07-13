<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use App\Filament\Resources\VendorOrders\VendorOrderResource;
use App\Models\VendorOrder;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only — a vendor order's own status transitions and fulfilment
 * actions live on VendorOrderResource, not here; this list just gives an
 * admin an at-a-glance breakdown per seller with a link through.
 */
class VendorOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'vendorOrders';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('vendor_order_number')
            ->columns([
                TextColumn::make('vendor_order_number')
                    ->label('Sub-order'),
                TextColumn::make('vendor.business_name')
                    ->label('Vendor'),
                TextColumn::make('order_items_count')
                    ->label('Items')
                    ->counts('orderItems'),
                TextColumn::make('total')
                    ->money('USD', divideBy: 100),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('view')
                    ->url(fn (VendorOrder $record) => VendorOrderResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}
