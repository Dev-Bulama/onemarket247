<?php

namespace App\Filament\Resources\Vendors\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Read-only ledger view — wallet balances only ever move through
 * App\Actions\Wallet\* actions, never edited here directly.
 */
class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'walletTransactions';

    protected static ?string $title = 'Wallet transactions';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('balance_bucket')
                    ->badge(),
                TextColumn::make('amount')
                    ->money('USD', divideBy: 100)
                    ->color(fn (int $state) => $state >= 0 ? 'success' : 'danger'),
                TextColumn::make('vendorOrder.vendor_order_number')
                    ->label('Order')
                    ->placeholder('—'),
                TextColumn::make('withdrawal.reference')
                    ->label('Withdrawal')
                    ->placeholder('—'),
                TextColumn::make('reason')
                    ->placeholder('—')
                    ->limit(40),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
