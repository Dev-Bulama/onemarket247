<?php

namespace App\Models;

use App\Enums\VendorApplicationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'vendor_id', 'vendor_subscription_plan_id', 'full_name', 'email', 'phone',
        'business_name', 'store_name', 'store_slug', 'country_id', 'state_id', 'city_id',
        'postal_code', 'address', 'registration_number', 'tax_identification_number',
        'identity_type', 'identity_number', 'store_category', 'store_description', 'website',
        'social_links', 'bank_name', 'bank_account_name', 'bank_account_number',
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'bank_account_name' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'status' => VendorApplicationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(VendorSubscriptionPlan::class, 'vendor_subscription_plan_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }
}
