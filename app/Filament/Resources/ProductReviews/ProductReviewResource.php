<?php

namespace App\Filament\Resources\ProductReviews;

use App\Filament\Resources\ProductReviews\Pages\ListProductReviews;
use App\Filament\Resources\ProductReviews\Pages\ViewProductReview;
use App\Filament\Resources\ProductReviews\Tables\ProductReviewsTable;
use App\Models\ProductReview;
use BackedEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Product reviews only this phase — vendor_reviews/delivery_reviews need a
 * completed order/shipment to review, neither of which exists until
 * Phase 12/15 (see docs/reports/phase-09-completion-report.md). Read-only +
 * action-driven, like VendorApplicationResource: reviews are written by
 * customers, only moderated here.
 */
class ProductReviewResource extends Resource
{
    protected static ?string $model = ProductReview::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Engagement';

    protected static ?string $navigationLabel = 'Reviews';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return ProductReviewsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Review')
                ->columns(2)
                ->schema([
                    TextEntry::make('product.name')->label('Product'),
                    TextEntry::make('customer.name')->label('Customer'),
                    TextEntry::make('rating')->suffix(' / 5'),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('title')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('body')->columnSpanFull(),
                    TextEntry::make('rejection_reason')->placeholder('—')->columnSpanFull(),
                ]),
            Section::make('Vendor response')
                ->schema([
                    TextEntry::make('vendor_response')->placeholder('No response yet')->columnSpanFull(),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductReviews::route('/'),
            'view' => ViewProductReview::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }
}
