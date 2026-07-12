<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'user_type', 'status', 'phone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_type' => UserType::class,
            'status' => UserStatus::class,
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function storeStaff(): HasMany
    {
        return $this->hasMany(StoreStaff::class);
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function twoFactorCredential(): HasOne
    {
        return $this->hasOne(TwoFactorCredential::class);
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function deviceSessions(): HasMany
    {
        return $this->hasMany(DeviceSession::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->twoFactorCredential()->whereNotNull('confirmed_at')->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => in_array($this->user_type, [UserType::SuperAdmin, UserType::Admin, UserType::Staff], true) && $this->isActive(),
            default => false,
        };
    }
}
