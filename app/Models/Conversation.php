<?php

namespace App\Models;

use App\Models\Scopes\BelongsToVendorScope;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A single chat thread between a vendor and one other party (a customer or
 * an admin) — see App\Models\ChatMessage for the messages within it. There
 * is at most one conversation per (vendor, user) pair regardless of which
 * product prompted it, matching how "contact seller" works on most
 * marketplaces; product_id only remembers the context it was started from.
 * The "other party" is always `user`; anyone who is NOT that user but has
 * access (the vendor owner or an authorised store staff member — see
 * ConversationPolicy) is on the vendor side.
 */
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToVendorScope);
    }

    protected $fillable = [
        'vendor_id', 'user_id', 'product_id', 'subject',
        'last_message_at', 'closed_at', 'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null;
    }

    public function isVendorSide(User $user): bool
    {
        return $user->id !== $this->user_id;
    }
}
