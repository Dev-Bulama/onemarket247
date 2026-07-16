<?php

namespace App\Filament\Vendor\Resources\Products\Tables;

use App\Actions\Product\SubmitProductForApprovalAction;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Support\PriceDisplay;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('brand.name')
                    ->placeholder('—'),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('price')
                    ->money(PriceDisplay::baseCurrencyCode())
                    ->placeholder('Varies')
                    ->sortable(),
                TextColumn::make('stock_quantity')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('stock_status')
                    ->badge(),
                IconColumn::make('is_featured')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(ProductStatus::class),
            ])
            ->recordActions([
                Action::make('submitForReview')
                    ->label('Submit for review')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Product $record) => in_array($record->status, [ProductStatus::Draft, ProductStatus::Rejected], true))
                    ->action(function (Product $record) {
                        app(SubmitProductForApprovalAction::class)->handle($record);
                        Notification::make()->title('Product submitted for review')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
