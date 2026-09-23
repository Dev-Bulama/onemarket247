<?php

namespace App\Filament\Resources\Agents\Tables;

use App\Enums\AgentStatus;
use App\Models\Agent;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AgentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('phone')
                    ->placeholder('—'),
                TextColumn::make('country.name')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('commission_rate')
                    ->numeric()
                    ->suffix('%')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('vendors_count')
                    ->label('Vendors onboarded')
                    ->counts('vendors'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(AgentStatus::class),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('warning')
                    ->schema([
                        Textarea::make('reason')->label('Reason')->required(),
                    ])
                    ->visible(fn (Agent $record) => auth()->user()?->can('agents.manage')
                        && $record->status === AgentStatus::Approved)
                    ->action(function (Agent $record, array $data) {
                        $record->update([
                            'status' => AgentStatus::Suspended,
                            'suspended_at' => now(),
                            'rejection_reason' => $data['reason'],
                        ]);
                        Notification::make()->title('Agent suspended')->success()->send();
                    }),
                Action::make('reactivate')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Agent $record) => auth()->user()?->can('agents.manage')
                        && $record->status === AgentStatus::Suspended)
                    ->action(function (Agent $record) {
                        $record->update(['status' => AgentStatus::Approved, 'suspended_at' => null]);
                        Notification::make()->title('Agent reactivated')->success()->send();
                    }),
                Action::make('deactivate')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This removes the agent from the "Registered Agent" dropdown vendors pick from. This can be undone by editing the agent later.')
                    ->visible(fn (Agent $record) => auth()->user()?->can('agents.manage')
                        && $record->status !== AgentStatus::Deactivated)
                    ->action(function (Agent $record) {
                        $record->update(['status' => AgentStatus::Deactivated]);
                        Notification::make()->title('Agent deactivated')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
