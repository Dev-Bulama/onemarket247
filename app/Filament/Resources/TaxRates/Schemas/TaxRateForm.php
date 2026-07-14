<?php

namespace App\Filament\Resources\TaxRates\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaxRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Select::make('tax_class_id')
                ->label('Tax class (leave blank to apply to all classes)')
                ->relationship('taxClass', 'name')
                ->searchable()
                ->preload(),
            Select::make('country_id')
                ->label('Country')
                ->relationship('country', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->live(),
            Select::make('state_id')
                ->label('State (optional, narrows to this country)')
                ->relationship('state', 'name', fn ($query, $get) => $query->where('country_id', $get('country_id')))
                ->searchable()
                ->preload(),
            Select::make('city_id')
                ->label('City (optional, narrows to this state)')
                ->relationship('city', 'name', fn ($query, $get) => $query->where('state_id', $get('state_id')))
                ->searchable()
                ->preload(),
            TextInput::make('postal_code')
                ->label('Postal code (optional, most specific)')
                ->maxLength(255),
            TextInput::make('rate_percent')
                ->label('Rate (%)')
                ->numeric()
                ->required()
                ->default(0),
            Toggle::make('is_active')
                ->default(true),
            TextInput::make('sort_order')
                ->numeric()
                ->default(0),
        ]);
    }
}
