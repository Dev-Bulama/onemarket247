<?php

namespace App\Filament\Resources\Stores\Schemas;

use App\Enums\StoreStatus;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('vendor_id')
                    ->relationship('vendor', 'business_name')
                    ->disabled()
                    ->dehydrated(false),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                Textarea::make('address')
                    ->columnSpanFull(),
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
                    ->preload(),
                Select::make('status')
                    ->options(StoreStatus::class)
                    ->default(StoreStatus::Active)
                    ->required(),
                Toggle::make('is_verified'),
                Toggle::make('is_featured'),
                TextInput::make('minimum_order_amount')
                    ->numeric()
                    ->helperText('Minor currency units (e.g. cents).'),
                TextInput::make('seo_title')
                    ->maxLength(255),
                TextInput::make('seo_description')
                    ->maxLength(255),
                KeyValue::make('social_links')
                    ->columnSpanFull(),
                KeyValue::make('working_hours')
                    ->columnSpanFull(),
                Textarea::make('vacation_message')
                    ->columnSpanFull(),
            ]);
    }
}
