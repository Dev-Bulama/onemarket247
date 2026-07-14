<?php

namespace App\Filament\Resources\PickupStations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PickupStationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('vendor_id')
                ->label('Vendor (leave blank for a platform-wide station)')
                ->relationship('vendor', 'business_name')
                ->searchable()
                ->preload(),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('phone')
                ->tel()
                ->maxLength(30),
            TextInput::make('address_line_1')
                ->required()
                ->maxLength(255),
            TextInput::make('address_line_2')
                ->maxLength(255),
            Select::make('country_id')
                ->relationship('country', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('state_id')
                ->relationship('state', 'name')
                ->searchable()
                ->preload(),
            Select::make('city_id')
                ->relationship('city', 'name')
                ->searchable()
                ->preload(),
            Toggle::make('is_active')
                ->default(true),
        ]);
    }
}
