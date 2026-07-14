<?php

namespace App\Models;

use App\Enums\DeliveryEvidenceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryEvidence extends Model
{
    use HasFactory;

    protected $fillable = ['delivery_assignment_id', 'type', 'file_path', 'recipient_name', 'notes'];

    protected function casts(): array
    {
        return [
            'type' => DeliveryEvidenceType::class,
        ];
    }

    public function deliveryAssignment(): BelongsTo
    {
        return $this->belongsTo(DeliveryAssignment::class);
    }
}
