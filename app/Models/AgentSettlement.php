<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mirrors App\Models\Withdrawal's shape, with one deliberate difference:
 * an Agent has no account to request a payout from (see Agent's
 * docblock), so a settlement is always created by an admin on the
 * agent's behalf (created_by) rather than a vendor self-service request.
 * Reuses WithdrawalStatus — the same pending/approved/paid/rejected
 * lifecycle applies, just without the vendor-only "cancelled" transition.
 */
class AgentSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'agent_id', 'amount', 'status',
        'rejection_reason', 'created_by', 'reviewed_by', 'reviewed_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => WithdrawalStatus::class,
            'reviewed_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    protected static function booted(): void
    {
        static::created(function (self $settlement) {
            if ($settlement->reference === null) {
                $settlement->updateQuietly(['reference' => sprintf('AS-%d-%06d', $settlement->created_at->year, $settlement->id)]);
            }
        });
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AgentWalletTransaction::class);
    }
}
