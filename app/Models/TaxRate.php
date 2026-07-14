<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tax_class_id', 'name', 'country_id', 'state_id', 'city_id',
        'postal_code', 'rate_percent', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function taxClass(): BelongsTo
    {
        return $this->belongsTo(TaxClass::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function computeTax(int $taxableAmount): int
    {
        return (int) round($taxableAmount * (float) $this->rate_percent / 100);
    }
}
