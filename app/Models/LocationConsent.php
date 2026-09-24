<?php

namespace App\Models;

use Database\Factories\LocationConsentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationConsent extends Model
{
    /** @use HasFactory<LocationConsentFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'is_enabled', 'enabled_at', 'disabled_at'];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'enabled_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
