<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItemTaxSnapshot extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['order_item_id', 'tax_rate_id', 'rate_percent', 'taxable_amount', 'tax_amount'];

    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:2',
            'taxable_amount' => 'integer',
            'tax_amount' => 'integer',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }
}
