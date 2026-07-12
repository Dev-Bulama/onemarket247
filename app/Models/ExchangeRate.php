<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeRate extends Model
{
    use HasFactory;

    protected $fillable = ['currency_id', 'rate', 'is_manual', 'fetched_at'];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:10',
            'is_manual' => 'boolean',
            'fetched_at' => 'datetime',
        ];
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
