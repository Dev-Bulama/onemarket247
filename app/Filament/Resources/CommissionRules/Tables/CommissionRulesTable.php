<?php

namespace App\Filament\Resources\CommissionRules\Tables;

use App\Enums\CommissionScopeType;
use App\Models\CommissionRule;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommissionRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scope_type')
                    ->badge(),
                TextColumn::make('scope')
                    ->label('Applies to')
                    ->state(fn (CommissionRule $record) => match ($record->scope_type) {
                        CommissionScopeType::Category => $record->category?->name,
                        CommissionScopeType::Product => $record->product?->name,
                        CommissionScopeType::Vendor => $record->vendor?->business_name,
                        CommissionScopeType::SubscriptionPlan => $record->subscriptionPlan?->name,
                        CommissionScopeType::Global => 'All vendors/products',
                    })
                    ->placeholder('—'),
                TextColumn::make('rate_type')
                    ->badge(),
                TextColumn::make('rate_value')
                    ->formatStateUsing(fn (CommissionRule $record) => $record->rate_type->value === 'percentage'
                        ? number_format((float) $record->rate_value, 2).'%'
                        : '$'.number_format((float) $record->rate_value, 2)),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('scope_type')
                    ->options(CommissionScopeType::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
