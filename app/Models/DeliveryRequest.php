<?php

namespace App\Models;

use App\Enums\DeliveryRequestStatus;
use Database\Factories\DeliveryRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeliveryRequest extends Model
{
    /** @use HasFactory<DeliveryRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'shipment_id', 'status', 'delivery_fee', 'required_by',
        'special_instructions', 'accepted_delivery_partner_id', 'accepted_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryRequestStatus::class,
            'delivery_fee' => 'integer',
            'required_by' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function acceptedPartner(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartner::class, 'accepted_delivery_partner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(DeliveryRequestNotification::class);
    }

    public function assignment(): HasOne
    {
        return $this->hasOne(DeliveryAssignment::class);
    }
}
