<?php

namespace App\Filament\Vendor\Resources\Coupons\Tables;

use App\Enums\CouponType;
use App\Support\PriceDisplay;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(fn ($state, $record) => $record->type === CouponType::Percentage
                        ? "{$state}%"
                        : PriceDisplay::format($state)),
                TextColumn::make('minimum_spend')
                    ->label('Min. spend')
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : PriceDisplay::format($state)),
                IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(CouponType::class),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
