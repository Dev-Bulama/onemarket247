<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Actions\Payment\RefundPaymentAction;
use App\Exceptions\PaymentGatewayException;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refund')
                ->color('danger')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->schema(fn (Payment $record) => [
                    TextInput::make('amount')
                        ->label('Amount to refund (USD)')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->maxValue(($record->amount - $record->refunded_amount) / 100)
                        ->default(($record->amount - $record->refunded_amount) / 100),
                ])
                ->visible(fn (Payment $record) => auth()->user()?->can('refunds.manage') && $record->isRefundable())
                ->action(function (Payment $record, array $data) {
                    try {
                        app(RefundPaymentAction::class)->handle($record, (int) round($data['amount'] * 100), auth()->user());
                        Notification::make()->title('Refund processed')->success()->send();
                    } catch (PaymentGatewayException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
