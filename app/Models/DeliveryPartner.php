<?php

namespace App\Models;

use App\Enums\DeliveryPartnerStatus;
use Database\Factories\DeliveryPartnerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A registered courier/rider — a roster/directory entity, not a
 * login-capable account (see the table's own migration docblock): a
 * partner accepts a delivery request and updates its progress entirely
 * through unguessable-link pages, never a password.
 */
class DeliveryPartner extends Model
{
    /** @use HasFactory<DeliveryPartnerFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name', 'email', 'phone', 'vehicle_type',
        'country_id', 'state_id', 'city_id', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryPartnerStatus::class,
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

    public function notifications(): HasMany
    {
        return $this->hasMany(DeliveryRequestNotification::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeliveryAssignment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', DeliveryPartnerStatus::Active);
    }
}
