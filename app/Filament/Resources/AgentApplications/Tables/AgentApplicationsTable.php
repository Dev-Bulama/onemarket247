<?php

namespace App\Filament\Resources\AgentApplications\Tables;

use App\Actions\Agent\ApproveAgentApplicationAction;
use App\Actions\Agent\RejectAgentApplicationAction;
use App\Enums\AgentApplicationStatus;
use App\Exceptions\AgentApplicationConflictException;
use App\Models\AgentApplication;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AgentApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->placeholder('—'),
                TextColumn::make('country.name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('documents_count')
                    ->label('Documents')
                    ->counts('documents'),
                TextColumn::make('reviewer.name')
                    ->label('Reviewed by')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(AgentApplicationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
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
                DeleteAction::make()
                    ->visible(fn (AgentApplication $record) => auth()->user()?->can('delete', $record) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }
}
