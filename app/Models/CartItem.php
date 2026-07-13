<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = ['cart_id', 'product_id', 'product_variation_id', 'quantity', 'unit_price', 'saved_for_later'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'saved_for_later' => 'boolean',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function currentUnitPrice(): ?int
    {
        return $this->variation?->price ?? $this->product->price;
    }

    public function isInStock(): bool
    {
        return $this->variation?->isInStock() ?? $this->product->isInStock();
    }

    public function hasPriceDrifted(): bool
    {
        return $this->currentUnitPrice() !== null && $this->currentUnitPrice() !== $this->unit_price;
    }

    public function lineTotal(): int
    {
        return $this->unit_price * $this->quantity;
    }
}
