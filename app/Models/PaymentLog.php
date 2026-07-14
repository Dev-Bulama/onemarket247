<?php

namespace App\Models;

use App\Enums\PaymentLogDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Insert-only audit trail of every gateway interaction — no updated_at
 * column and no code path ever updates or deletes a row once written, the
 * same convention as AuditLog.
 */
class PaymentLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = ['payment_id', 'gateway', 'direction', 'payload'];

    protected function casts(): array
    {
        return [
            'direction' => PaymentLogDirection::class,
            'payload' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
