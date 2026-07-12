<?php

namespace App\Filament\Resources\Vendors\Tables;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class VendorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('commission_rate')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->placeholder('Platform default'),
                IconColumn::make('is_verified')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_featured')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country.name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(VendorStatus::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Vendor $record) => auth()->user()?->can('vendors.approve')
                        && in_array($record->status, [VendorStatus::Pending, VendorStatus::UnderReview], true))
                    ->action(function (Vendor $record) {
                        $record->update(['status' => VendorStatus::Approved, 'approved_at' => now()]);
                        Notification::make()->title('Vendor approved')->success()->send();
                    }),
                Action::make('reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('rejection_reason')->required(),
                    ])
                    ->visible(fn (Vendor $record) => auth()->user()?->can('vendors.approve')
                        && in_array($record->status, [VendorStatus::Pending, VendorStatus::UnderReview], true))
                    ->action(function (Vendor $record, array $data) {
                        $record->update(['status' => VendorStatus::Rejected, 'rejection_reason' => $data['rejection_reason']]);
                        Notification::make()->title('Vendor rejected')->success()->send();
                    }),
                Action::make('suspend')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('warning')
                    ->schema([
                        Textarea::make('rejection_reason')->label('Reason')->required(),
                    ])
                    ->visible(fn (Vendor $record) => auth()->user()?->can('vendors.suspend')
                        && $record->status === VendorStatus::Approved)
                    ->action(function (Vendor $record, array $data) {
                        $record->update([
                            'status' => VendorStatus::Suspended,
                            'suspended_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        Notification::make()->title('Vendor suspended')->success()->send();
                    }),
                Action::make('reactivate')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Vendor $record) => auth()->user()?->can('vendors.suspend')
                        && $record->status === VendorStatus::Suspended)
                    ->action(function (Vendor $record) {
                        $record->update(['status' => VendorStatus::Approved, 'suspended_at' => null]);
                        Notification::make()->title('Vendor reactivated')->success()->send();
                    }),
                Action::make('terminate')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('This permanently deactivates the vendor. This cannot be undone from here.')
                    ->visible(fn (Vendor $record) => auth()->user()?->can('vendors.terminate')
                        && ! in_array($record->status, [VendorStatus::Banned, VendorStatus::Deactivated], true))
                    ->action(function (Vendor $record) {
                        $record->update(['status' => VendorStatus::Deactivated]);
                        Notification::make()->title('Vendor terminated')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
