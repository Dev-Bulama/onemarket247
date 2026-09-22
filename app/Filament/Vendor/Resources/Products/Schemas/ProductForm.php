<?php

namespace App\Filament\Vendor\Resources\Products\Schemas;

use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Support\Filament\MinorUnitsInput;
use App\Support\PriceDisplay;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * vendor_id/status/review fields are system-managed (injected on create,
 * transitioned via the "submit for review"/admin approve-reject actions),
 * never exposed here — see CreateProduct::handleRecordCreation().
 */
class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('brand_id')
                    ->label('Brand')
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
                    ->maxLength(255)
                    ->live(onBlur: true),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('sku')
                    ->label('SKU')
                    ->unique(ignoreRecord: true)
                    ->visible(fn (Get $get) => $get('type') !== ProductType::Variable->value),
                Select::make('type')
                    ->options(ProductType::class)
                    ->default(ProductType::Simple->value)
                    ->live()
                    ->required(),
                CheckboxList::make('categories')
                    ->relationship('categories', 'name')
                    ->columns(2),
                CheckboxList::make('tags')
                    ->relationship('tags', 'name')
                    ->columns(3),
                Textarea::make('short_description')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->numeric()
                    ->required(fn (Get $get) => $get('type') !== ProductType::Variable->value)
                    ->visible(fn (Get $get) => $get('type') !== ProductType::Variable->value)
                    ->prefix(PriceDisplay::baseCurrencyCode())
                    ->helperText('The selling price, e.g. 29.99.')
                    ->afterStateHydrated(MinorUnitsInput::hydrate())
                    ->dehydrateStateUsing(MinorUnitsInput::dehydrate()),
                TextInput::make('compare_at_price')
                    ->numeric()
                    ->visible(fn (Get $get) => $get('type') !== ProductType::Variable->value)
                    ->prefix(PriceDisplay::baseCurrencyCode())
                    ->helperText('Optional "was" price shown struck through, e.g. 39.99.')
                    ->afterStateHydrated(MinorUnitsInput::hydrate())
                    ->dehydrateStateUsing(MinorUnitsInput::dehydrate()),
                Toggle::make('manage_stock')
                    ->default(true)
                    ->visible(fn (Get $get) => $get('type') !== ProductType::Variable->value),
                TextInput::make('stock_quantity')
                    ->numeric()
                    ->visible(fn (Get $get) => $get('type') !== ProductType::Variable->value),
                Select::make('stock_status')
                    ->options(StockStatus::class)
                    ->default(StockStatus::InStock->value)
                    ->required()
                    ->visible(fn (Get $get) => $get('type') !== ProductType::Variable->value),
                TextInput::make('low_stock_threshold')
                    ->numeric()
                    ->visible(fn (Get $get) => $get('type') !== ProductType::Variable->value),
                TextInput::make('weight')->numeric(),
                TextInput::make('length')->numeric(),
                TextInput::make('width')->numeric(),
                TextInput::make('height')->numeric(),
                FileUpload::make('images')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->disk('public')
                    ->directory('tmp-product-media')
                    ->visibility('public')
                    ->columnSpanFull(),
                FileUpload::make('video')
                    ->label('Product video (optional)')
                    ->acceptedFileTypes(['video/mp4', 'video/quicktime'])
                    ->disk('public')
                    ->directory('tmp-product-media')
                    ->visibility('public')
                    ->maxSize(51200)
                    ->helperText('MP4 or MOV, up to 50MB. Especially useful for property, vehicle, and machinery listings. Uploading a new video replaces the current one.')
                    ->columnSpanFull(),
                FileUpload::make('digital_files')
                    ->multiple()
                    ->disk('local')
                    ->directory('tmp-product-digital-files')
                    ->visibility('private')
                    ->visible(fn (Get $get) => $get('type') === ProductType::Digital->value)
                    ->columnSpanFull(),
                TextInput::make('seo_title')->maxLength(255),
                TextInput::make('seo_description')->maxLength(255),
            ]);
    }
}
