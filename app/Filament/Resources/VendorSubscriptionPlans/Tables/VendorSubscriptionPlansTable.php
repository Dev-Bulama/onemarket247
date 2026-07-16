<?php

namespace App\Filament\Resources\VendorSubscriptionPlans\Tables;

use App\Support\PriceDisplay;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VendorSubscriptionPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('price')
                    ->money(PriceDisplay::baseCurrencyCode())
                    ->sortable(),
                TextColumn::make('billing_period')
                    ->badge(),
                TextColumn::make('max_products')
                    ->placeholder('Unlimited')
                    ->sortable(),
                TextColumn::make('commission_rate_override')
                    ->suffix('%')
                    ->placeholder('Vendor default')
                    ->sortable(),
                IconColumn::make('is_default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
