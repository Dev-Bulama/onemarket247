<?php

namespace App\Models;

use App\Enums\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id', 'customer_id', 'rating', 'title', 'body', 'status',
        'is_verified_purchase', 'vendor_response', 'vendor_responded_at',
        'reviewed_by', 'reviewed_at', 'rejection_reason', 'helpful_count',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'status' => ReviewStatus::class,
            'is_verified_purchase' => 'boolean',
            'vendor_responded_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'helpful_count' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(ReviewVote::class, 'votable');
    }

    public function isApproved(): bool
    {
        return $this->status === ReviewStatus::Approved;
    }
}
