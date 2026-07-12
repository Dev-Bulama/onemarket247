<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorSubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'price', 'billing_period', 'max_products',
        'commission_rate_override', 'features', 'is_default', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'billing_period' => BillingPeriod::class,
            'max_products' => 'integer',
            'commission_rate_override' => 'decimal:2',
            'features' => 'array',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(VendorSubscription::class);
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    protected static function booted(): void
    {
        static::saving(function (self $plan) {
            if (! $plan->is_default) {
                return;
            }

            $query = static::query();

            if ($plan->exists) {
                $query->where('id', '!=', $plan->id);
            }

            $query->update(['is_default' => false]);
        });
    }
}
