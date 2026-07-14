<?php

namespace App\Filament\Resources\Currencies\Pages;

use App\Filament\Resources\Currencies\CurrencyResource;
use App\Models\ExchangeRate;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCurrency extends EditRecord
{
    protected static string $resource = CurrencyResource::class;

    private float $exchangeRate = 1;

    private bool $exchangeRateIsManual = true;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->exchangeRate = (float) ($data['exchange_rate'] ?? 1);
        $this->exchangeRateIsManual = (bool) ($data['exchange_rate_is_manual'] ?? true);

        unset($data['exchange_rate'], $data['exchange_rate_is_manual']);

        return $data;
    }

    protected function afterSave(): void
    {
        ExchangeRate::updateOrCreate(
            ['currency_id' => $this->record->id],
            [
                'rate' => $this->exchangeRate,
                'is_manual' => $this->exchangeRateIsManual,
                'fetched_at' => now(),
            ],
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
