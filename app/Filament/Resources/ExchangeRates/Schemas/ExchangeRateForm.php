<?php

namespace App\Filament\Resources\ExchangeRates\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ExchangeRateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('rate')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.0000000001)
                    ->helperText('Units of this currency per 1 unit of the default currency.'),
                Toggle::make('is_manual')
                    ->label('Manually set')
                    ->default(true)
                    ->required(),
                DateTimePicker::make('fetched_at')
                    ->label('Last updated'),
            ]);
    }
}
