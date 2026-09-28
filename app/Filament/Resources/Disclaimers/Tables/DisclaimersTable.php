<?php

namespace App\Filament\Resources\Disclaimers\Tables;

use App\Enums\DisclaimerTrigger;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class DisclaimersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('trigger')
                    ->badge(),
                IconColumn::make('requires_acceptance')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('start_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('end_at')
                    ->dateTime()
                    ->placeholder('—'),
                TextColumn::make('acceptances_count')
                    ->label('Acceptances')
                    ->counts('acceptances'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('trigger')
                    ->options(DisclaimerTrigger::class),
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
