<?php

namespace App\Filament\Resources\Currencies\Schemas;

use App\Enums\CurrencySymbolPosition;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CurrencyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->length(3)
                    ->unique(ignoreRecord: true),
                TextInput::make('symbol')
                    ->required()
                    ->maxLength(10),
                Select::make('symbol_position')
                    ->options(CurrencySymbolPosition::class)
                    ->default(CurrencySymbolPosition::Before)
                    ->required(),
                TextInput::make('decimal_places')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(4)
                    ->default(2),
                TextInput::make('thousand_separator')
                    ->required()
                    ->maxLength(1)
                    ->default(','),
                TextInput::make('decimal_separator')
                    ->required()
                    ->maxLength(1)
                    ->default('.'),
                Toggle::make('is_default')
                    ->helperText('Setting this unsets any other default currency.')
                    ->default(false)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
