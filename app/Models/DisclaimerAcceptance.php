<?php

namespace App\Models;

use Database\Factories\DisclaimerAcceptanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisclaimerAcceptance extends Model
{
    /** @use HasFactory<DisclaimerAcceptanceFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = ['disclaimer_id', 'user_id', 'guest_identifier', 'ip_address', 'user_agent'];

    public function disclaimer(): BelongsTo
    {
        return $this->belongsTo(Disclaimer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
