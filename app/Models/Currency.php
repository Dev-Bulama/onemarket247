<?php

namespace App\Models;

use App\Enums\CurrencySymbolPosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'symbol', 'symbol_position', 'decimal_places',
        'thousand_separator', 'decimal_separator', 'is_default', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'symbol_position' => CurrencySymbolPosition::class,
            'decimal_places' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function exchangeRate(): HasOne
    {
        return $this->hasOne(ExchangeRate::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $currency) {
            if (! $currency->is_default) {
                return;
            }

            $query = static::query();

            if ($currency->exists) {
                $query->where('id', '!=', $currency->id);
            }

            $query->update(['is_default' => false]);
        });
    }
}
