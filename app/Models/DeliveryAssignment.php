<?php

namespace App\Models;

use App\Enums\DeliveryAssignmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_id', 'assignee_name', 'assignee_phone', 'status',
        'assigned_at', 'delivered_at', 'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryAssignmentStatus::class,
            'assigned_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(DeliveryEvidence::class);
    }
}
