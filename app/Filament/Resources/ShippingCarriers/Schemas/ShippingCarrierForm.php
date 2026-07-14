<?php

namespace App\Filament\Resources\ShippingCarriers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShippingCarrierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('tracking_url_template')
                ->label('Tracking URL template')
                ->helperText('Use {tracking_number} as a placeholder, e.g. https://carrier.example/track/{tracking_number}')
                ->maxLength(255),
            Toggle::make('is_active')
                ->default(true),
        ]);
    }
}
