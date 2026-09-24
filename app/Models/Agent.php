<?php

namespace App\Models;

use App\Enums\AgentStatus;
use Database\Factories\AgentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A OneMarket247 field agent who assists customers with applying to
 * become a vendor (see Vendor::agent() / VendorApplication::agent()) —
 * a roster/directory entity, not a login-capable account: nothing in this
 * codebase gives an Agent a panel guard or dashboard, so no User row is
 * created for one. See App\Models\AgentApplication for how an agent is
 * vetted before landing here.
 */
class Agent extends Model
{
    /** @use HasFactory<AgentFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name', 'email', 'phone', 'country_id', 'state_id', 'city_id',
        'postal_code', 'address', 'identity_type', 'identity_number',
        'commission_rate', 'bank_name', 'bank_account_name', 'bank_account_number',
        'status', 'notes', 'approved_at', 'suspended_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => AgentStatus::class,
            'commission_rate' => 'decimal:2',
            'bank_account_name' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
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

    public function documents(): HasMany
    {
        return $this->hasMany(AgentDocument::class);
    }

    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    public function vendorApplications(): HasMany
    {
        return $this->hasMany(VendorApplication::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(AgentWallet::class);
    }

    public function walletTransactions(): HasManyThrough
    {
        return $this->hasManyThrough(AgentWalletTransaction::class, AgentWallet::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(AgentSettlement::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', AgentStatus::Approved);
    }
}
