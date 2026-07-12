<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Insert-only login audit trail — see the owning migration and
 * docs/architecture/02-database-erd.md §3.
 */
class LoginHistory extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'guard', 'ip_address', 'user_agent',
        'device_fingerprint', 'is_new_device', 'successful',
    ];

    protected function casts(): array
    {
        return [
            'is_new_device' => 'boolean',
            'successful' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
