<?php

namespace App\Filament\Resources\ShippingZones\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShippingZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('sort_order')
                ->required()
                ->numeric()
                ->default(0)
                ->helperText('Zones are matched most-specific-location first regardless of this value; a zone with no locations at all acts as the "rest of world" catch-all.'),
            Toggle::make('is_active')
                ->default(true),
        ]);
    }
}
