<?php

namespace App\Filament\Resources\Currencies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurrenciesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('symbol')
                    ->searchable(),
                TextColumn::make('symbol_position')
                    ->badge()
                    ->searchable(),
                TextColumn::make('exchangeRate.rate')
                    ->label('Rate')
                    ->numeric(decimalPlaces: 4)
                    ->placeholder('Not set'),
                TextColumn::make('decimal_places')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('thousand_separator')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('decimal_separator')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_default')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
