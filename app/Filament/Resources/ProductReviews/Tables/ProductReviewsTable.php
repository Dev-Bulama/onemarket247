<?php

namespace App\Filament\Resources\ProductReviews\Tables;

use App\Enums\ReviewStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductReviewsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('title')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                IconColumn::make('is_verified_purchase')
                    ->boolean(),
                TextColumn::make('helpful_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ReviewStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
