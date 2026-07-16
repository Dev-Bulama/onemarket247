<?php

namespace App\Filament\Resources\Products\Tables;

use App\Actions\Product\ApproveProductAction;
use App\Actions\Product\RejectProductAction;
use App\Enums\ProductStatus;
use App\Models\Product;
use App\Support\PriceDisplay;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('vendor.business_name')
                    ->label('Vendor')
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
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Product $record) => auth()->user()?->can('approve', Product::class)
                        && $record->status === ProductStatus::PendingApproval)
                    ->action(function (Product $record) {
                        app(ApproveProductAction::class)->handle($record, auth()->user());
                        Notification::make()->title('Product approved')->success()->send();
                    }),
                Action::make('reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')->required(),
                    ])
                    ->visible(fn (Product $record) => auth()->user()?->can('approve', Product::class)
                        && $record->status === ProductStatus::PendingApproval)
                    ->action(function (Product $record, array $data) {
                        app(RejectProductAction::class)->handle($record, $data['reason'], auth()->user());
                        Notification::make()->title('Product rejected')->success()->send();
                    }),
                Action::make('toggleFeatured')
                    ->label(fn (Product $record) => $record->is_featured ? 'Unfeature' : 'Feature')
                    ->icon(Heroicon::OutlinedStar)
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->can('feature', Product::class))
                    ->action(fn (Product $record) => $record->update(['is_featured' => ! $record->is_featured])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
