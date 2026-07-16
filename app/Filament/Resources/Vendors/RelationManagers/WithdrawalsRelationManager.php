<?php

namespace App\Filament\Resources\Vendors\RelationManagers;

use App\Filament\Resources\Withdrawals\WithdrawalResource;
use App\Models\Withdrawal;
use App\Support\PriceDisplay;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only — approve/reject/mark-paid actions live on WithdrawalResource's
 * ViewWithdrawal page, this tab just links through per vendor.
 */
class WithdrawalsRelationManager extends RelationManager
{
    protected static string $relationship = 'withdrawals';

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
                    ->label('Requested')
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
                    ->url(fn (Withdrawal $record) => WithdrawalResource::getUrl('view', ['record' => $record])),
            ])
            ->toolbarActions([]);
    }
}
