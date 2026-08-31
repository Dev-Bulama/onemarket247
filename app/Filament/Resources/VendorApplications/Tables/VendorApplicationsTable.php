<?php

namespace App\Filament\Resources\VendorApplications\Tables;

use App\Actions\Vendor\ApproveVendorApplicationAction;
use App\Actions\Vendor\RejectVendorApplicationAction;
use App\Enums\VendorApplicationStatus;
use App\Exceptions\VendorApplicationConflictException;
use App\Models\VendorApplication;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VendorApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('business_name')
                    ->searchable(),
                TextColumn::make('store_name')
                    ->searchable(),
                TextColumn::make('country.name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('agent_full_name')
                    ->label('Agent')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subscriptionPlan.name')
                    ->label('Requested plan')
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
                    ->options(VendorApplicationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('approve')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (VendorApplication $record) => auth()->user()?->can('vendors.approve')
                        && $record->status === VendorApplicationStatus::Pending)
                    ->action(function (VendorApplication $record) {
                        try {
                            app(ApproveVendorApplicationAction::class)->handle($record, auth()->user());
                            Notification::make()->title('Vendor application approved')->success()->send();
                        } catch (VendorApplicationConflictException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                Action::make('reject')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->schema([
                        Textarea::make('reason')->required(),
                    ])
                    ->visible(fn (VendorApplication $record) => auth()->user()?->can('vendors.approve')
                        && $record->status === VendorApplicationStatus::Pending)
                    ->action(function (VendorApplication $record, array $data) {
                        app(RejectVendorApplicationAction::class)->handle($record, $data['reason'], auth()->user());
                        Notification::make()->title('Vendor application rejected')->success()->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (VendorApplication $record) => auth()->user()?->can('delete', $record) ?? false),
            ]);
    }
}
