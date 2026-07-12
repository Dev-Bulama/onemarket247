<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'label', 'full_name', 'phone', 'country_id', 'state_id', 'city_id',
        'address_line_1', 'address_line_2', 'postal_code', 'latitude', 'longitude',
        'is_default_shipping', 'is_default_billing',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_default_shipping' => 'boolean',
            'is_default_billing' => 'boolean',
        ];
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
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

    /**
     * The User id that "owns" this address regardless of which polymorphic
     * addressable type (User, Vendor, Store) it is attached to — the single
     * place AddressPolicy resolves ownership from.
     */
    public function ownerUserId(): ?int
    {
        return match (true) {
            $this->addressable instanceof User => $this->addressable->id,
            $this->addressable instanceof Vendor => $this->addressable->user_id,
            $this->addressable instanceof Store => $this->addressable->vendor->user_id,
            default => null,
        };
    }
}
