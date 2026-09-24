<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Mirrors App\Models\VendorWallet exactly: balance columns are cached/
 * derived from agent_wallet_transactions — see the owning migration's
 * docblock. Only wallet actions (App\Actions\Wallet\*Agent*) ever mutate
 * them, always inside a locked transaction alongside a ledger row.
 */
class AgentWallet extends Model
{
    use HasFactory;

    protected $fillable = ['agent_id', 'pending_balance', 'available_balance', 'reserved_balance', 'withdrawn_balance'];

    protected function casts(): array
    {
        return [
            'pending_balance' => 'integer',
            'available_balance' => 'integer',
            'reserved_balance' => 'integer',
            'withdrawn_balance' => 'integer',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AgentWalletTransaction::class);
    }
}
