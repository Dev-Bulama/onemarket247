<?php

namespace App\Models;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'type', 'value', 'minimum_spend', 'starts_at', 'expires_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'minimum_spend' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $coupon) {
            $coupon->code = strtoupper($coupon->code);
        });
    }

    public function isValidNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    public function discountFor(int $subtotal): int
    {
        $discount = $this->type === CouponType::Percentage
            ? intdiv($subtotal * $this->value, 100)
            : $this->value;

        return min($discount, $subtotal);
    }
}
