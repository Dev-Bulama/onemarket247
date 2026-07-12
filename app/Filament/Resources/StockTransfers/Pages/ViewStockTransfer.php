<?php

namespace App\Filament\Resources\StockTransfers\Pages;

use App\Actions\Inventory\CancelStockTransferAction;
use App\Actions\Inventory\CompleteStockTransferAction;
use App\Actions\Inventory\DispatchStockTransferAction;
use App\Enums\StockTransferStatus;
use App\Filament\Resources\StockTransfers\StockTransferResource;
use App\Models\StockTransfer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewStockTransfer extends ViewRecord
{
    protected static string $resource = StockTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dispatch')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::Pending)
                ->action(function (StockTransfer $record) {
                    app(DispatchStockTransferAction::class)->handle($record, auth()->user());
                    Notification::make()->title('Transfer dispatched')->success()->send();
                }),
            Action::make('complete')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (StockTransfer $record) => $record->status === StockTransferStatus::InTransit)
                ->action(function (StockTransfer $record) {
                    app(CompleteStockTransferAction::class)->handle($record, auth()->user());
                    Notification::make()->title('Transfer completed')->success()->send();
                }),
            Action::make('cancel')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (StockTransfer $record) => in_array($record->status, [StockTransferStatus::Pending, StockTransferStatus::InTransit], true))
                ->action(function (StockTransfer $record) {
                    app(CancelStockTransferAction::class)->handle($record, auth()->user());
                    Notification::make()->title('Transfer cancelled')->success()->send();
                }),
        ];
    }
}
