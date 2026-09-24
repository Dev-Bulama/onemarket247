<?php

namespace App\Models;

use Database\Factories\LocationPingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationPing extends Model
{
    /** @use HasFactory<LocationPingFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'latitude', 'longitude', 'accuracy', 'recorded_at'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The single most recent ping per user — what the admin live map reads
     * as each user's "current" location, without needing a denormalized
     * cache column to keep in sync.
     */
    public function scopeLatestPerUser(Builder $query): Builder
    {
        return $query->whereIn('id', function ($subQuery) {
            $subQuery->selectRaw('MAX(id)')->from('location_pings')->groupBy('user_id');
        });
    }
}
