<?php

namespace App\Models;

use App\Enums\AgentApplicationStatus;
use Database\Factories\AgentApplicationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentApplication extends Model
{
    /** @use HasFactory<AgentApplicationFactory> */
    use HasFactory;

    protected $fillable = [
        'reference_number', 'agent_id', 'full_name', 'email', 'phone',
        'country_id', 'state_id', 'city_id', 'postal_code', 'address',
        'identity_type', 'identity_number', 'notes',
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AgentApplicationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AgentDocument::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $application) {
            if ($application->reference_number === null) {
                $application->updateQuietly(['reference_number' => sprintf('AA-%d-%06d', $application->created_at->year, $application->id)]);
            }
        });
    }
}
