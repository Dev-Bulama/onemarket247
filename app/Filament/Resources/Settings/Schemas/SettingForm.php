<?php

namespace App\Filament\Resources\Settings\Schemas;

use App\Enums\SettingType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Dot-notation key, e.g. app.default_currency'),
                Textarea::make('value')
                    ->columnSpanFull()
                    ->helperText('Booleans: "1" or "0". JSON: valid JSON text.'),
                Select::make('type')
                    ->options(SettingType::class)
                    ->default(SettingType::String)
                    ->required()
                    ->live(),
                TextInput::make('group')
                    ->maxLength(255),
            ]);
    }
}
