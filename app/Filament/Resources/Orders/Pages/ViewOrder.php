<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download-invoice')
                ->label('Download invoice')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->url(fn (Order $record) => route('orders.invoice', $record))
                ->openUrlInNewTab(),
        ];
    }
}
