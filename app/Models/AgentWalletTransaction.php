<?php

namespace App\Models;

use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Insert-only ledger — mirrors App\Models\VendorWalletTransaction. No
 * code path ever updates or deletes a row once written. Reuses the same
 * WalletTransactionType/WalletBalanceBucket enums as the vendor ledger —
 * they describe generic ledger semantics, not vendor-specific ones.
 */
class AgentWalletTransaction extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'agent_wallet_id', 'vendor_order_id', 'agent_settlement_id',
        'type', 'balance_bucket', 'amount', 'reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'balance_bucket' => WalletBalanceBucket::class,
            'amount' => 'integer',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(AgentWallet::class, 'agent_wallet_id');
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    public function agentSettlement(): BelongsTo
    {
        return $this->belongsTo(AgentSettlement::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
