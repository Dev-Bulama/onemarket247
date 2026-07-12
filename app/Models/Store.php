<?php

namespace App\Models;

use App\Enums\StoreStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id', 'name', 'slug', 'description', 'email', 'phone', 'address',
        'country_id', 'state_id', 'city_id', 'status', 'is_verified', 'is_featured',
        'minimum_order_amount', 'seo_title', 'seo_description', 'social_links',
        'working_hours', 'vacation_message',
    ];

    protected function casts(): array
    {
        return [
            'status' => StoreStatus::class,
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'social_links' => 'array',
            'working_hours' => 'array',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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

    public function staff(): HasMany
    {
        return $this->hasMany(StoreStaff::class);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function isOpen(): bool
    {
        return $this->status === StoreStatus::Active;
    }
}
