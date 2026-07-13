<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['product_id', 'customer_id', 'question', 'is_answered'];

    protected function casts(): array
    {
        return [
            'is_answered' => 'boolean',
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

    public function answers(): HasMany
    {
        return $this->hasMany(ProductAnswer::class);
    }
}
