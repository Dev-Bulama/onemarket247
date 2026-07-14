<?php

namespace App\Models;

use App\Enums\ShippingRateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_zone_id', 'shipping_class_id', 'name', 'rate_type',
        'base_amount', 'per_kg_amount', 'free_shipping_min_amount', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rate_type' => ShippingRateType::class,
            'base_amount' => 'integer',
            'per_kg_amount' => 'integer',
            'free_shipping_min_amount' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }

    public function shippingClass(): BelongsTo
    {
        return $this->belongsTo(ShippingClass::class);
    }

    /**
     * $weightKg is the total shipment weight; free_shipping_min_amount
     * overrides any rate type once the subtotal threshold is met.
     */
    public function computeCost(float $weightKg, int $subtotal): int
    {
        if ($this->free_shipping_min_amount !== null && $subtotal >= $this->free_shipping_min_amount) {
            return 0;
        }

        return match ($this->rate_type) {
            ShippingRateType::Free => 0,
            ShippingRateType::PerWeight => $this->base_amount + (int) ceil($weightKg * ($this->per_kg_amount ?? 0)),
            ShippingRateType::Flat => $this->base_amount,
        };
    }
}
