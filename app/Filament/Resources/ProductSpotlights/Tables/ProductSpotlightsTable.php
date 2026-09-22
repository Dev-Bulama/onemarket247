<?php

namespace App\Filament\Resources\ProductSpotlights\Tables;

use App\Enums\SpotlightDisplayArea;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductSpotlightsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('display_area')
                    ->label('Placement')
                    ->badge(),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('—'),
                TextColumn::make('position')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime()
                    ->placeholder('Immediately')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('display_area')
                    ->label('Placement')
                    ->options(SpotlightDisplayArea::class),
                TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('position')
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
