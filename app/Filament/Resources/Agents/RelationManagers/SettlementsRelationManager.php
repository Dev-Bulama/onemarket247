<?php

namespace App\Filament\Resources\Agents\RelationManagers;

use App\Filament\Resources\AgentSettlements\AgentSettlementResource;
use App\Models\AgentSettlement;
use App\Support\PriceDisplay;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only — mirrors Vendor's own WithdrawalsRelationManager. Approve/
 * reject/mark-paid actions live on AgentSettlementResource's
 * ViewAgentSettlement page, this tab just links through per agent.
 */
class SettlementsRelationManager extends RelationManager
{
    protected static string $relationship = 'settlements';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('amount')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([
                Action::make('view')
                    ->url(fn (AgentSettlement $record) => AgentSettlementResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}
