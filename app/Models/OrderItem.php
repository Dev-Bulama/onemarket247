<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_order_id', 'product_id', 'product_variation_id', 'warehouse_id',
        'product_name', 'sku', 'unit_price', 'quantity', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
        ];
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sellable(): Product|ProductVariation
    {
        return $this->variation ?? $this->product;
    }

    public function commission(): HasOne
    {
        return $this->hasOne(OrderItemCommission::class);
    }

    public function taxSnapshot(): HasOne
    {
        return $this->hasOne(OrderItemTaxSnapshot::class);
    }
}
