<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Actions\Payment\ConfirmBankTransferPaymentAction;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\PriceDisplay;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable(),
                TextColumn::make('gateway')
                    ->placeholder('—'),
                TextColumn::make('amount')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100)
                    ->sortable(),
                TextColumn::make('refunded_amount')
                    ->label('Refunded')
                    ->money(PriceDisplay::baseCurrencyCode(), divideBy: 100),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(PaymentStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('confirmBankTransfer')
                    ->label('Confirm bank transfer')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record) => auth()->user()?->can('payments.manage')
                        && $record->gateway === 'bank_transfer'
                        && $record->status === PaymentStatus::Pending)
                    ->action(function (Payment $record) {
                        app(ConfirmBankTransferPaymentAction::class)->handle($record, auth()->user());
                        Notification::make()->title('Bank transfer confirmed — order marked paid')->success()->send();
                    }),
            ]);
    }
}
