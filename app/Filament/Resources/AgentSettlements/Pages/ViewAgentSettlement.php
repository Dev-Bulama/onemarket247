<?php

namespace App\Filament\Resources\AgentSettlements\Pages;

use App\Actions\Settlement\ApproveAgentSettlementAction;
use App\Actions\Settlement\MarkAgentSettlementPaidAction;
use App\Actions\Settlement\RejectAgentSettlementAction;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InvalidWithdrawalTransitionException;
use App\Filament\Resources\AgentSettlements\AgentSettlementResource;
use App\Models\AgentSettlement;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewAgentSettlement extends ViewRecord
{
    protected static string $resource = AgentSettlementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->color('success')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->requiresConfirmation()
                ->visible(fn (AgentSettlement $record) => auth()->user()?->can('agents.manage')
                    && $record->status === WithdrawalStatus::Pending)
                ->action(function (AgentSettlement $record) {
                    try {
                        app(ApproveAgentSettlementAction::class)->handle($record, auth()->user());
                        Notification::make()->title('Settlement approved')->success()->send();
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
                ->visible(fn (AgentSettlement $record) => auth()->user()?->can('agents.manage')
                    && in_array($record->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true))
                ->action(function (AgentSettlement $record, array $data) {
                    try {
                        app(RejectAgentSettlementAction::class)->handle($record, $data['reason'], auth()->user());
                        Notification::make()->title('Settlement rejected')->success()->send();
                    } catch (InvalidWithdrawalTransitionException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('mark-paid')
                ->label('Mark as paid')
                ->color('success')
                ->icon(Heroicon::OutlinedBanknotes)
                ->requiresConfirmation()
                ->modalDescription('Confirm the payout has actually been sent to the agent before marking this paid.')
                ->visible(fn (AgentSettlement $record) => auth()->user()?->can('agents.manage')
                    && $record->status === WithdrawalStatus::Approved)
                ->action(function (AgentSettlement $record) {
                    try {
                        app(MarkAgentSettlementPaidAction::class)->handle($record, auth()->user());
                        Notification::make()->title('Settlement marked as paid')->success()->send();
                    } catch (InvalidWithdrawalTransitionException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
