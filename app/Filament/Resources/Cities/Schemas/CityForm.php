<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('country_id')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('state_id')
                    ->relationship('state', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
