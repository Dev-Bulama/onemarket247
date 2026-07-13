<?php

namespace App\Filament\Vendor\Resources\VendorOrders\Pages;

use App\Actions\Order\CancelVendorOrderAction;
use App\Actions\Order\UpdateVendorOrderStatusAction;
use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Filament\Vendor\Resources\VendorOrders\VendorOrderResource;
use App\Models\VendorOrder;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;

class ViewVendorOrder extends ViewRecord
{
    protected static string $resource = VendorOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download-packing-slip')
                ->label('Download packing slip')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(fn (VendorOrder $record) => route('packing-slips.download', $record))
                ->openUrlInNewTab(),

            ...$this->transitionActions(),

            Action::make('cancel')
                ->color('danger')
                ->icon(Heroicon::OutlinedXCircle)
                ->schema([
                    Textarea::make('reason')->required()->maxLength(500),
                ])
                ->visible(fn (VendorOrder $record) => Gate::allows('update', $record)
                    && in_array($record->status, CancelVendorOrderAction::CANCELLABLE_FROM, true))
                ->action(function (VendorOrder $record, array $data) {
                    try {
                        app(CancelVendorOrderAction::class)->handle($record, $data['reason'], auth()->user());
                        Notification::make()->title('Order cancelled')->success()->send();
                    } catch (InvalidOrderTransitionException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function transitionActions(): array
    {
        $record = $this->getRecord();

        if (! Gate::allows('update', $record)) {
            return [];
        }

        return array_map(
            fn (VendorOrderStatus $target) => Action::make('transition-'.$target->value)
                ->label('Mark as '.$target->getLabel())
                ->color('success')
                ->schema([
                    Textarea::make('note')->label('Note (optional)')->maxLength(500),
                ])
                ->action(function (VendorOrder $record, array $data) use ($target) {
                    try {
                        app(UpdateVendorOrderStatusAction::class)->handle($record, $target, $data['note'] ?: null, auth()->user());
                        Notification::make()->title('Status updated to '.$target->getLabel())->success()->send();
                    } catch (InvalidOrderTransitionException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
            UpdateVendorOrderStatusAction::nextStatusesFor($record->status)
        );
    }
}
