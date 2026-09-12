<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Cache of IP -> country lookups against the external geolocation API, so
 * the same IP is never looked up twice. Keyed by ip_address (see the owning
 * migration) rather than a surrogate id.
 */
class IpGeolocation extends Model
{
    public const UPDATED_AT = null;

    protected $primaryKey = 'ip_address';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['ip_address', 'country_code', 'resolved_at'];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }
}
