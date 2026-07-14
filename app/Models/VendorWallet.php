<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Balance columns are cached/derived from vendor_wallet_transactions —
 * see the owning migration's docblock. Only wallet actions
 * (App\Actions\Wallet\*) ever mutate them, always inside a locked
 * transaction alongside a ledger row.
 */
class VendorWallet extends Model
{
    use HasFactory;

    protected $fillable = ['vendor_id', 'pending_balance', 'available_balance', 'reserved_balance', 'withdrawn_balance'];

    protected function casts(): array
    {
        return [
            'pending_balance' => 'integer',
            'available_balance' => 'integer',
            'reserved_balance' => 'integer',
            'withdrawn_balance' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(VendorWalletTransaction::class);
    }
}
