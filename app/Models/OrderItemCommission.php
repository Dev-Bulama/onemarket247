<?php

namespace App\Models;

use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable snapshot — see the owning migration's docblock. No code path
 * ever updates a row once written.
 */
class OrderItemCommission extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'order_item_id', 'commission_rule_id', 'rate_type', 'rate_value',
        'gross_amount', 'commission_amount', 'net_amount',
    ];

    protected function casts(): array
    {
        return [
            'rate_type' => CommissionType::class,
            'rate_value' => 'decimal:2',
            'gross_amount' => 'integer',
            'commission_amount' => 'integer',
            'net_amount' => 'integer',
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function commissionRule(): BelongsTo
    {
        return $this->belongsTo(CommissionRule::class);
    }
}
