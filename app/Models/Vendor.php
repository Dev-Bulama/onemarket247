<?php

namespace App\Models;

use App\Enums\VendorStatus;
use App\Enums\VendorSubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'business_name', 'registration_number', 'tax_identification_number',
        'identity_type', 'identity_number', 'status', 'commission_rate', 'is_verified',
        'is_featured', 'bank_name', 'bank_account_name', 'bank_account_number',
        'country_id', 'state_id', 'city_id', 'postal_code', 'address',
        'approved_at', 'suspended_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => VendorStatus::class,
            'commission_rate' => 'decimal:2',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'bank_account_name' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(VendorApplication::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(VendorSubscription::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function currentSubscription(): ?VendorSubscription
    {
        return $this->subscriptions()
            ->where('status', VendorSubscriptionStatus::Active)
            ->latest('starts_at')
            ->first();
    }

    public function canAccessDashboard(): bool
    {
        return $this->status->allowsDashboardAccess();
    }
}
