<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['product_question_id', 'answered_by', 'answer'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(ProductQuestion::class, 'product_question_id');
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }
}
