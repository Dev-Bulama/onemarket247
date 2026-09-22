<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Support\PriceDisplay;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only from the admin panel, same rationale as DigitalFilesRelationManager
 * — variations are created/edited by the owning vendor (see
 * App\Filament\Vendor\Resources\Products\RelationManagers\VariationsRelationManager),
 * admins only need visibility for review purposes.
 */
class VariationsRelationManager extends RelationManager
{
    protected static string $relationship = 'variations';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('sku')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->getStateUsing(fn ($record) => $record->getFirstMediaUrl('images') ?: null),
                TextColumn::make('sku')
                    ->searchable(),
                TextColumn::make('attributeValues.value')
                    ->badge()
                    ->label('Attributes'),
                TextColumn::make('price')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                TextColumn::make('stock_quantity')
                    ->numeric(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
