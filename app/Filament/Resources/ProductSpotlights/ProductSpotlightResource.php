<?php

namespace App\Filament\Resources\ProductSpotlights;

use App\Filament\Concerns\GatedByPermission;
use App\Filament\Resources\ProductSpotlights\Pages\CreateProductSpotlight;
use App\Filament\Resources\ProductSpotlights\Pages\EditProductSpotlight;
use App\Filament\Resources\ProductSpotlights\Pages\ListProductSpotlights;
use App\Filament\Resources\ProductSpotlights\Schemas\ProductSpotlightForm;
use App\Filament\Resources\ProductSpotlights\Tables\ProductSpotlightsTable;
use App\Models\ProductSpotlight;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Admin-controlled promotion of hand-picked products into curated storefront
 * placements (homepage, category pages, search results, ...) with an
 * optional start/end schedule — see App\Actions\Product\SpotlightProductsAction
 * for how each placement reads these rows, and App\Models\ProductSpotlight
 * for the active-window logic.
 */
class ProductSpotlightResource extends Resource
{
    use GatedByPermission;

    protected static string $managePermission = 'products.feature';

    protected static ?string $model = ProductSpotlight::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?string $navigationLabel = 'Product Spotlight';

    public static function form(Schema $schema): Schema
    {
        return ProductSpotlightForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductSpotlightsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductSpotlights::route('/'),
            'create' => CreateProductSpotlight::route('/create'),
            'edit' => EditProductSpotlight::route('/{record}/edit'),
        ];
    }
}
