<?php

namespace App\Models;

use App\Enums\DisclaimerTrigger;
use Database\Factories\DisclaimerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Disclaimer extends Model
{
    /** @use HasFactory<DisclaimerFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'content', 'trigger', 'requires_acceptance',
        'start_at', 'end_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'trigger' => DisclaimerTrigger::class,
            'requires_acceptance' => 'boolean',
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(DisclaimerAcceptance::class);
    }

    /**
     * Active, and either has no scheduling window at all or the current
     * moment falls inside it.
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('start_at')->orWhere('start_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('end_at')->orWhere('end_at', '>=', now()));
    }
}
