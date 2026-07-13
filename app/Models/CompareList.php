<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompareList extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'compare_list_items')->withTimestamps();
    }
}
