<?php

namespace App\Filament\Resources\Languages\Schemas;

use App\Enums\LanguageDirection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class LanguageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('native_name')
                    ->maxLength(255),
                TextInput::make('code')
                    ->required()
                    ->maxLength(10)
                    ->unique(ignoreRecord: true),
                Select::make('direction')
                    ->options(LanguageDirection::class)
                    ->default(LanguageDirection::Ltr)
                    ->required(),
                Toggle::make('is_default')
                    ->helperText('Setting this unsets any other default language.')
                    ->default(false)
                    ->required(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
