<?php

namespace App\Filament\Resources\AgentApplications\Pages;

use App\Actions\Agent\ApproveAgentApplicationAction;
use App\Actions\Agent\RejectAgentApplicationAction;
use App\Enums\AgentApplicationStatus;
use App\Exceptions\AgentApplicationConflictException;
use App\Filament\Resources\AgentApplications\AgentApplicationResource;
use App\Models\AgentApplication;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewAgentApplication extends ViewRecord
{
    protected static string $resource = AgentApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (AgentApplication $record) => auth()->user()?->can('agents.manage')
                    && $record->status === AgentApplicationStatus::Pending)
                ->action(function (AgentApplication $record) {
                    try {
                        app(ApproveAgentApplicationAction::class)->handle($record, auth()->user());
                        Notification::make()->title('Agent application approved')->success()->send();
                    } catch (AgentApplicationConflictException $exception) {
                        Notification::make()->title($exception->getMessage())->danger()->send();
                    }
                }),
            Action::make('reject')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->schema([
                    Textarea::make('reason')->required(),
                ])
                ->visible(fn (AgentApplication $record) => auth()->user()?->can('agents.manage')
                    && $record->status === AgentApplicationStatus::Pending)
                ->action(function (AgentApplication $record, array $data) {
                    app(RejectAgentApplicationAction::class)->handle($record, $data['reason'], auth()->user());
                    Notification::make()->title('Agent application rejected')->success()->send();
                }),
        ];
    }
}
