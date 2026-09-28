<?php

namespace App\Filament\Resources\Disclaimers\Schemas;

use App\Enums\DisclaimerTrigger;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DisclaimerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Textarea::make('content')
                    ->required()
                    ->rows(6)
                    ->columnSpanFull(),
                Select::make('trigger')
                    ->options(DisclaimerTrigger::class)
                    ->required()
                    ->helperText('The first three (on first visit / once per session / once per user) share a single site-wide pop-up slot; only one of them is ever shown at a time.'),
                Toggle::make('requires_acceptance')
                    ->label('Requires an explicit Accept & Continue click')
                    ->default(true)
                    ->helperText('Off shows a plain "Got it" dismiss instead of an acceptance the system tracks as consent.'),
                Toggle::make('is_active')
                    ->default(true),
                DateTimePicker::make('start_at')
                    ->label('Start date')
                    ->helperText('Leave blank to start immediately.'),
                DateTimePicker::make('end_at')
                    ->label('End date')
                    ->helperText('Leave blank to run indefinitely.'),
            ])
            ->columns(2);
    }
}
