<?php

namespace App\Support\Auth;

use Illuminate\Support\Facades\Session;

/**
 * Holds the "credentials verified, awaiting 2FA code" state between the
 * login attempt and the challenge form, for whichever guard is
 * authenticating. Nothing here implies the user is logged in — Auth::login()
 * is only called once TwoFactorChallengeController accepts a valid code.
 */
class TwoFactorSession
{
    private const KEY = 'auth.two_factor.pending';

    public static function stash(int $userId, string $guard, bool $remember): void
    {
        Session::put(self::KEY, [
            'user_id' => $userId,
            'guard' => $guard,
            'remember' => $remember,
        ]);
    }

    public static function pending(): ?array
    {
        return Session::get(self::KEY);
    }

    public static function clear(): void
    {
        Session::forget(self::KEY);
    }
}
