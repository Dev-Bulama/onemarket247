<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only — moderation happens on ProductReviewResource, this is just
 * visibility into what a given customer has written.
 */
class ProductReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'productReviews';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('product.name'),
                TextColumn::make('rating')->numeric(),
                TextColumn::make('title')->placeholder('—'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->headerActions([])
            ->recordActions([
                Action::make('view')
                    ->url(fn ($record) => route('filament.admin.resources.product-reviews.view', $record)),
            ])
            ->toolbarActions([]);
    }
}
