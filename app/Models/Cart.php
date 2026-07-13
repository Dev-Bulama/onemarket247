<?php

namespace App\Models;

use App\Enums\CartStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'session_token', 'status'];

    protected function casts(): array
    {
        return [
            'status' => CartStatus::class,
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('saved_for_later', false);
    }

    public function savedItems(): HasMany
    {
        return $this->items()->where('saved_for_later', true);
    }

    public function coupon(): HasOne
    {
        return $this->hasOne(CartCoupon::class);
    }

    public function subtotal(): int
    {
        return $this->activeItems->sum(fn (CartItem $item) => $item->unit_price * $item->quantity);
    }

    public function discount(): int
    {
        return $this->coupon?->discount_amount ?? 0;
    }

    public function total(): int
    {
        return max(0, $this->subtotal() - $this->discount());
    }
}
