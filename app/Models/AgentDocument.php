<?php

namespace App\Models;

use App\Enums\AgentDocumentStatus;
use App\Enums\AgentDocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id', 'agent_application_id', 'type', 'file_path', 'status',
        'rejection_reason', 'verified_by', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AgentDocumentType::class,
            'status' => AgentDocumentStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function agentApplication(): BelongsTo
    {
        return $this->belongsTo(AgentApplication::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
