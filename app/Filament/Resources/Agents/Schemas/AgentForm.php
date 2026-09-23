<?php

namespace App\Filament\Resources\Agents\Schemas;

use App\Enums\AgentStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AgentForm
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
                    ->maxLength(30),
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
                TextInput::make('address')
                    ->maxLength(500),
                TextInput::make('postal_code')
                    ->maxLength(20),
                TextInput::make('identity_type')
                    ->maxLength(100),
                TextInput::make('identity_number')
                    ->maxLength(100),
                TextInput::make('commission_rate')
                    ->numeric()
                    ->suffix('%')
                    ->minValue(0)
                    ->maxValue(100),
                Select::make('status')
                    ->options(AgentStatus::class)
                    ->required()
                    ->default(AgentStatus::Approved),
                Textarea::make('notes')
                    ->maxLength(2000)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }
}
