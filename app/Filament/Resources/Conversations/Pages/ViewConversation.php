<?php

namespace App\Filament\Resources\Conversations\Pages;

use App\Filament\Resources\Conversations\ConversationResource;
use App\Models\Conversation;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewConversation extends ViewRecord
{
    protected static string $resource = ConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('close')
                ->label('Close conversation')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Conversation $record) => ! $record->isClosed())
                ->action(function (Conversation $record) {
                    $record->update(['closed_at' => now(), 'closed_by' => auth()->id()]);
                    Notification::make()->title('Conversation closed')->success()->send();
                }),
            Action::make('reopen')
                ->label('Reopen conversation')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (Conversation $record) => $record->isClosed())
                ->action(function (Conversation $record) {
                    $record->update(['closed_at' => null, 'closed_by' => null]);
                    Notification::make()->title('Conversation reopened')->success()->send();
                }),
        ];
    }
}
