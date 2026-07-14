<?php

namespace App\Models;

use App\Enums\WalletBalanceBucket;
use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Insert-only ledger — see the owning migration's docblock. No code path
 * ever updates or deletes a row once written.
 */
class VendorWalletTransaction extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'vendor_wallet_id', 'vendor_order_id', 'withdrawal_id',
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
        return $this->belongsTo(VendorWallet::class, 'vendor_wallet_id');
    }

    public function vendorOrder(): BelongsTo
    {
        return $this->belongsTo(VendorOrder::class);
    }

    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
