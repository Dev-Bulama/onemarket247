<?php

namespace App\Filament\Resources\AgentSettlements\Schemas;

use App\Models\Agent;
use App\Support\Filament\MinorUnitsInput;
use App\Support\PriceDisplay;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AgentSettlementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('agent_id')
                ->label('Agent')
                ->options(fn () => Agent::active()->pluck('full_name', 'id'))
                ->searchable()
                ->required(),
            TextInput::make('amount')
                ->numeric()
                ->prefix(PriceDisplay::baseCurrencyCode())
                ->helperText('The amount to settle with this agent, e.g. 29.99.')
                ->afterStateHydrated(MinorUnitsInput::hydrate())
                ->dehydrateStateUsing(MinorUnitsInput::dehydrate())
                ->required(),
        ]);
    }
}
