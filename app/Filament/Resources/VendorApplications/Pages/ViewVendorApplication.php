<?php

namespace App\Filament\Resources\VendorApplications\Pages;

use App\Actions\Vendor\ApproveVendorApplicationAction;
use App\Actions\Vendor\RejectVendorApplicationAction;
use App\Enums\VendorApplicationStatus;
use App\Exceptions\VendorApplicationConflictException;
use App\Filament\Resources\VendorApplications\VendorApplicationResource;
use App\Models\VendorApplication;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewVendorApplication extends ViewRecord
{
    protected static string $resource = VendorApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
        ];
    }
}
