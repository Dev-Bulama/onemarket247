<?php

namespace App\Models;

use Database\Factories\DeliveryRequestNotificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRequestNotification extends Model
{
    /** @use HasFactory<DeliveryRequestNotificationFactory> */
    use HasFactory;

    protected $fillable = ['delivery_request_id', 'delivery_partner_id', 'token', 'responded_at'];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function deliveryRequest(): BelongsTo
    {
        return $this->belongsTo(DeliveryRequest::class);
    }

    public function deliveryPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class);
    }
}
