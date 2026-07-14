<?php

namespace App\Filament\Resources\Currencies\Pages;

use App\Filament\Resources\Currencies\CurrencyResource;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCurrency extends CreateRecord
{
    protected static string $resource = CurrencyResource::class;

    private float $exchangeRate = 1;

    private bool $exchangeRateIsManual = true;

    protected function handleRecordCreation(array $data): Model
    {
        $this->exchangeRate = (float) ($data['exchange_rate'] ?? 1);
        $this->exchangeRateIsManual = (bool) ($data['exchange_rate_is_manual'] ?? true);

        unset($data['exchange_rate'], $data['exchange_rate_is_manual']);

        return Currency::create($data);
    }

    protected function afterCreate(): void
    {
        ExchangeRate::create([
            'currency_id' => $this->record->id,
            'rate' => $this->exchangeRate,
            'is_manual' => $this->exchangeRateIsManual,
            'fetched_at' => now(),
        ]);
    }
}
