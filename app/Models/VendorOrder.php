<?php

namespace App\Models;

use App\Enums\VendorOrderStatus;
use App\Models\Scopes\BelongsToVendorScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'vendor_id', 'vendor_order_number',
        'subtotal', 'discount_amount', 'shipping_amount', 'tax_amount', 'total', 'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'shipping_amount' => 'integer',
            'tax_amount' => 'integer',
            'total' => 'integer',
            'status' => VendorOrderStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToVendorScope);

        static::created(function (self $vendorOrder) {
            if ($vendorOrder->vendor_order_number === null) {
                // "-V1", "-V2", ... reflects this vendor's ordinal position
                // among the siblings of the same order (per the example in
                // docs/architecture/09-lifecycles.md), not this row's own
                // (globally shared) auto-increment id.
                $ordinal = self::withoutGlobalScopes()
                    ->where('order_id', $vendorOrder->order_id)
                    ->where('id', '<=', $vendorOrder->id)
                    ->count();

                $vendorOrder->updateQuietly([
                    'vendor_order_number' => $vendorOrder->order->order_number.'-V'.$ordinal,
                ]);
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
