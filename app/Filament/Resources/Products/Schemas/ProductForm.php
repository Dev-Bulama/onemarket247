<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductType;
use App\Enums\StockStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

/**
 * Admin-side editing only — products are created by vendors (see
 * App\Filament\Vendor\Resources\Products), never originated from this
 * panel, so status/review fields are handled by the table's approve/reject
 * actions rather than this form (same pattern as VendorForm in Phase 4).
 */
class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->searchable(),
                Select::make('shipping_class_id')
                    ->label('Shipping class')
                    ->relationship('shippingClass', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('tax_class_id')
                    ->label('Tax class')
                    ->relationship('taxClass', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->options(ProductType::class)
                    ->required(),
                Textarea::make('short_description')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->numeric()
                    ->helperText('Minor currency units (e.g. cents).'),
                TextInput::make('compare_at_price')
                    ->numeric()
                    ->helperText('Minor currency units (e.g. cents).'),
                Toggle::make('manage_stock')
                    ->default(true),
                TextInput::make('stock_quantity')
                    ->numeric(),
                Select::make('stock_status')
                    ->options(StockStatus::class)
                    ->required(),
                Toggle::make('is_featured'),
                TextInput::make('seo_title')
                    ->maxLength(255),
                TextInput::make('seo_description')
                    ->maxLength(255),
            ]);
    }
}
