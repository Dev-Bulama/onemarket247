<?php

namespace App\Filament\Widgets;

use App\Enums\WithdrawalStatus;
use App\Models\Withdrawal;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PendingWithdrawalsWidget extends TableWidget
{
    protected static ?string $heading = 'Pending Withdrawals';

    public static function canView(): bool
    {
        return auth()->user()?->can('withdrawals.view') ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Withdrawal::query()->where('status', WithdrawalStatus::Pending))
            ->columns([
                TextColumn::make('vendor.business_name')
                    ->label('Vendor'),
                TextColumn::make('amount')
                    ->money('USD', divideBy: 100),
                TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime(),
            ])
            ->defaultSort('created_at')
            ->paginated([5, 10, 25]);
    }
}
