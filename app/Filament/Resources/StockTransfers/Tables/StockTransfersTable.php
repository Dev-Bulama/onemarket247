<?php

namespace App\Filament\Resources\StockTransfers\Tables;

use App\Enums\StockTransferStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockTransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fromWarehouse.name')
                    ->label('From')
                    ->searchable(),
                TextColumn::make('toWarehouse.name')
                    ->label('To')
                    ->searchable(),
                TextColumn::make('fromWarehouse.vendor.business_name')
                    ->label('Vendor'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('requester.name')
                    ->label('Requested by')
                    ->placeholder('—'),
                TextColumn::make('requested_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('requested_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(StockTransferStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
