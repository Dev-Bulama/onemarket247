<?php

namespace App\Filament\Resources\ProductSpotlights\Schemas;

use App\Enums\SpotlightDisplayArea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductSpotlightForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('display_area')
                    ->options(SpotlightDisplayArea::class)
                    ->required()
                    ->live(),
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn (Get $get) => $get('display_area') === SpotlightDisplayArea::Category)
                    ->helperText('Which category page this product is spotlighted on.'),
                TextInput::make('position')
                    ->numeric()
                    ->default(0)
                    ->required()
                    ->helperText('Lower numbers are shown first within this placement.'),
                DateTimePicker::make('starts_at')
                    ->label('Starts at')
                    ->helperText('Leave blank to start immediately.'),
                DateTimePicker::make('ends_at')
                    ->label('Ends at')
                    ->helperText('Leave blank to run indefinitely.')
                    ->after('starts_at'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),
            ]);
    }
}
