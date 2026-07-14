<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The dedupe row for "duplicate callbacks can't duplicate payments" — see
 * the create_webhook_events_table migration. A row is inserted the moment
 * a webhook is first seen (inside the same transaction that processes it)
 * so a replayed delivery hits the unique (gateway, event_id) constraint
 * before doing anything else; processed_at records when handling
 * actually finished.
 */
class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = ['gateway', 'event_id', 'payload', 'processed_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
