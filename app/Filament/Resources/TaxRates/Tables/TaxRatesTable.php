<?php

namespace App\Filament\Resources\TaxRates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('taxClass.name')
                    ->label('Tax class')
                    ->placeholder('All classes'),
                TextColumn::make('country.name')
                    ->label('Country')
                    ->searchable(),
                TextColumn::make('state.name')
                    ->label('State')
                    ->placeholder('—'),
                TextColumn::make('city.name')
                    ->label('City')
                    ->placeholder('—'),
                TextColumn::make('postal_code')
                    ->placeholder('—'),
                TextColumn::make('rate_percent')
                    ->label('Rate')
                    ->suffix('%')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
