<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('iso2')
                    ->label('ISO 3166-1 alpha-2')
                    ->required()
                    ->length(2)
                    ->unique(ignoreRecord: true),
                TextInput::make('iso3')
                    ->label('ISO 3166-1 alpha-3')
                    ->required()
                    ->length(3)
                    ->unique(ignoreRecord: true),
                TextInput::make('phone_code')
                    ->tel(),
                TextInput::make('currency_code')
                    ->length(3),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
