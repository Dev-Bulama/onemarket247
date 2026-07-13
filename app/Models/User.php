<?php

namespace App\Models;

use App\Enums\StoreStaffStatus;
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

    public function wishlist(): HasOne
    {
        return $this->hasOne(Wishlist::class, 'customer_id');
    }

    public function compareList(): HasOne
    {
        return $this->hasOne(CompareList::class, 'customer_id');
    }

    public function productReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class, 'customer_id');
    }

    public function productQuestions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class, 'customer_id');
    }

    public function wishlistOrCreate(): Wishlist
    {
        return $this->wishlist()->first() ?? $this->wishlist()->create();
    }

    public function compareListOrCreate(): CompareList
    {
        return $this->compareList()->first() ?? $this->compareList()->create();
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->twoFactorCredential()->whereNotNull('confirmed_at')->exists();
    }

    /**
     * The Vendor a "vendor" guard actor is acting on behalf of: their own
     * Vendor record for an owner, or their store's owning Vendor for staff.
     * Used by BelongsToVendorScope to isolate vendor-owned data — the
     * VendorStaff branch MUST query Store without its own BelongsToVendorScope,
     * since that scope calls back into this method to resolve its filter
     * (a normal Eloquent load here would recurse infinitely).
     */
    public function actingVendorId(): ?int
    {
        return match ($this->user_type) {
            UserType::VendorOwner => $this->vendor?->id,
            UserType::VendorStaff => $this->storeStaff()
                ->with(['store' => fn ($query) => $query->withoutGlobalScopes()])
                ->first()?->store?->vendor_id,
            default => null,
        };
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => in_array($this->user_type, [UserType::SuperAdmin, UserType::Admin, UserType::Staff], true) && $this->isActive(),
            'vendor' => in_array($this->user_type, [UserType::VendorOwner, UserType::VendorStaff], true)
                && $this->isActive()
                && $this->canAccessVendorDashboard(),
            default => false,
        };
    }

    public function canAccessVendorDashboard(): bool
    {
        if ($this->user_type === UserType::VendorOwner) {
            return $this->vendor?->canAccessDashboard() ?? false;
        }

        $staff = $this->storeStaff()
            ->with(['store' => fn ($query) => $query->withoutGlobalScopes()->with('vendor')])
            ->first();

        return $staff
            && $staff->status === StoreStaffStatus::Active
            && ($staff->store?->vendor?->canAccessDashboard() ?? false);
    }
}
