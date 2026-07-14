<?php

namespace App\Filament\Resources\Withdrawals\Pages;

use App\Actions\Withdrawal\ApproveWithdrawalAction;
use App\Actions\Withdrawal\MarkWithdrawalPaidAction;
use App\Actions\Withdrawal\RejectWithdrawalAction;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InvalidWithdrawalTransitionException;
use App\Filament\Resources\Withdrawals\WithdrawalResource;
use App\Models\Withdrawal;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewWithdrawal extends ViewRecord
{
    protected static string $resource = WithdrawalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->color('success')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->requiresConfirmation()
                ->visible(fn (Withdrawal $record) => auth()->user()?->can('withdrawals.approve')
                    && $record->status === WithdrawalStatus::Pending)
                ->action(function (Withdrawal $record) {
                    try {
                        app(ApproveWithdrawalAction::class)->handle($record, auth()->user());
                        Notification::make()->title('Withdrawal approved')->success()->send();
                    } catch (InvalidWithdrawalTransitionException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('reject')
                ->color('danger')
                ->icon(Heroicon::OutlinedXCircle)
                ->schema([
                    Textarea::make('reason')->required()->maxLength(500),
                ])
                ->visible(fn (Withdrawal $record) => auth()->user()?->can('withdrawals.approve')
                    && in_array($record->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true))
                ->action(function (Withdrawal $record, array $data) {
                    try {
                        app(RejectWithdrawalAction::class)->handle($record, $data['reason'], auth()->user());
                        Notification::make()->title('Withdrawal rejected')->success()->send();
                    } catch (InvalidWithdrawalTransitionException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('mark-paid')
                ->label('Mark as paid')
                ->color('success')
                ->icon(Heroicon::OutlinedBanknotes)
                ->requiresConfirmation()
                ->modalDescription('Confirm the payout has actually been sent to the vendor before marking this paid.')
                ->visible(fn (Withdrawal $record) => auth()->user()?->can('withdrawals.approve')
                    && $record->status === WithdrawalStatus::Approved)
                ->action(function (Withdrawal $record) {
                    try {
                        app(MarkWithdrawalPaidAction::class)->handle($record, auth()->user());
                        Notification::make()->title('Withdrawal marked as paid')->success()->send();
                    } catch (InvalidWithdrawalTransitionException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
