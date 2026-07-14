<?php

namespace App\Models;

use App\Enums\CommissionScopeType;
use App\Enums\CommissionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'scope_type', 'category_id', 'product_id', 'vendor_id', 'subscription_plan_id',
        'rate_type', 'rate_value', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scope_type' => CommissionScopeType::class,
            'rate_type' => CommissionType::class,
            'rate_value' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(VendorSubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * @param  int  $grossAmount  minor-unit amount to compute commission from
     *
     * rate_value is a decimal in both cases (matching its column type) —
     * a percentage (e.g. 12.50 for 12.5%) or a dollar amount (e.g. 5.00
     * for $5.00), converted to minor units here rather than stored as
     * minor units directly.
     */
    public function computeCommission(int $grossAmount): int
    {
        $commission = $this->rate_type === CommissionType::Percentage
            ? (int) round($grossAmount * (float) $this->rate_value / 100)
            : (int) round((float) $this->rate_value * 100);

        return min($commission, $grossAmount);
    }
}
