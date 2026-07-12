<?php

namespace App\Models;

use App\Enums\StoreStaffStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreStaff extends Model
{
    use HasFactory;

    protected $fillable = ['store_id', 'user_id', 'status', 'invited_at', 'joined_at'];

    protected function casts(): array
    {
        return [
            'status' => StoreStaffStatus::class,
            'invited_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
