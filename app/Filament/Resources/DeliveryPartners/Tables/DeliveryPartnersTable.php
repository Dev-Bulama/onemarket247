<?php

namespace App\Filament\Resources\DeliveryPartners\Tables;

use App\Enums\DeliveryPartnerStatus;
use App\Models\DeliveryPartner;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeliveryPartnersTable
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
                    ->searchable(),
                TextColumn::make('vehicle_type')
                    ->placeholder('—'),
                TextColumn::make('city.name')
                    ->label('Coverage city')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('assignments_count')
                    ->label('Deliveries')
                    ->counts('assignments'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(DeliveryPartnerStatus::class),
            ])
            ->recordActions([
                Action::make('suspend')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (DeliveryPartner $record) => $record->status === DeliveryPartnerStatus::Active)
                    ->action(function (DeliveryPartner $record) {
                        $record->update(['status' => DeliveryPartnerStatus::Suspended]);
                        Notification::make()->title('Delivery partner suspended')->success()->send();
                    }),
                Action::make('reactivate')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (DeliveryPartner $record) => $record->status === DeliveryPartnerStatus::Suspended)
                    ->action(function (DeliveryPartner $record) {
                        $record->update(['status' => DeliveryPartnerStatus::Active]);
                        Notification::make()->title('Delivery partner reactivated')->success()->send();
                    }),
                Action::make('deactivate')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This removes the partner from delivery-request alerts. This can be undone by editing them later.')
                    ->visible(fn (DeliveryPartner $record) => $record->status !== DeliveryPartnerStatus::Deactivated)
                    ->action(function (DeliveryPartner $record) {
                        $record->update(['status' => DeliveryPartnerStatus::Deactivated]);
                        Notification::make()->title('Delivery partner deactivated')->success()->send();
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
