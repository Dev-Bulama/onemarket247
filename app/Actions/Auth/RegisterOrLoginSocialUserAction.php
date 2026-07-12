<?php

namespace App\Actions\Auth;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\CustomerProfile;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class RegisterOrLoginSocialUserAction
{
    public function handle(string $provider, SocialiteUser $socialiteUser): User
    {
        return DB::transaction(function () use ($provider, $socialiteUser) {
            $socialAccount = SocialAccount::where('provider', $provider)
                ->where('provider_user_id', $socialiteUser->getId())
                ->first();

            if ($socialAccount) {
                return $socialAccount->user;
            }

            $user = $socialiteUser->getEmail()
                ? User::where('email', $socialiteUser->getEmail())->first()
                : null;

            if (! $user) {
                $user = User::create([
                    'name' => $socialiteUser->getName() ?: $socialiteUser->getNickname() ?: 'OneMarket247 Customer',
                    'email' => $socialiteUser->getEmail() ?: $provider.'-'.$socialiteUser->getId().'@users.onemarket247.local',
                    'password' => Hash::make(Str::random(40)),
                    'user_type' => UserType::Customer,
                    'status' => UserStatus::Active,
                ]);

                // email_verified_at is intentionally excluded from User's
                // fillable list (a form/API caller must never be able to
                // mass-assign it); the OAuth provider vouching for the
                // address is what earns it here, so it's set explicitly.
                if ($socialiteUser->getEmail()) {
                    $user->forceFill(['email_verified_at' => now()])->save();
                }

                CustomerProfile::create(['user_id' => $user->id]);
            }

            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_user_id' => $socialiteUser->getId(),
                'avatar' => $socialiteUser->getAvatar(),
            ]);

            return $user;
        });
    }
}
