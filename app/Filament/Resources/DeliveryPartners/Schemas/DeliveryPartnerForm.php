<?php

namespace App\Filament\Resources\DeliveryPartners\Schemas;

use App\Enums\DeliveryPartnerStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class DeliveryPartnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('full_name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('phone')
                    ->required()
                    ->maxLength(30),
                TextInput::make('vehicle_type')
                    ->maxLength(50)
                    ->helperText('E.g. bike, car, van.'),
                Select::make('country_id')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('state_id')
                    ->relationship('state', 'name')
                    ->searchable()
                    ->preload(),
                Select::make('city_id')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload()
                    ->helperText('Used to match this partner to delivery requests in their coverage area.'),
                Select::make('status')
                    ->options(DeliveryPartnerStatus::class)
                    ->required()
                    ->default(DeliveryPartnerStatus::Active),
                Textarea::make('notes')
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
