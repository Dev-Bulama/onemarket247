<?php

namespace App\Filament\Resources\TaxClasses\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TaxClassForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Textarea::make('description')
                ->columnSpanFull(),
            Toggle::make('is_default')
                ->helperText('Setting this unsets any other default tax class.')
                ->default(false),
            Toggle::make('is_active')
                ->default(true),
        ]);
    }
}
