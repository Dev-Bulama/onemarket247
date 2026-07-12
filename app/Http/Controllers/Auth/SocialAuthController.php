<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterOrLoginSocialUserAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Google is fully functional. Facebook uses the same Socialite driver and
 * activates once its credentials are configured. Apple is intentionally
 * "architecture only" per the Phase 3 brief — see config/services.php for
 * why — and returns a clear error instead of a broken redirect.
 */
class SocialAuthController extends Controller
{
    private const SUPPORTED_DRIVERS = ['google', 'facebook'];

    public function redirect(string $provider): RedirectResponse
    {
        if (! $this->isUsable($provider)) {
            return $this->notConfigured($provider);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, RegisterOrLoginSocialUserAction $action): RedirectResponse
    {
        if (! $this->isUsable($provider)) {
            return $this->notConfigured($provider);
        }

        $socialiteUser = Socialite::driver($provider)->user();
        $user = $action->handle($provider, $socialiteUser);

        // The OAuth provider's own authentication already constitutes a
        // second factor, so social login bypasses our TOTP challenge here.
        Auth::guard('web')->login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('account.dashboard'));
    }

    private function isUsable(string $provider): bool
    {
        return in_array($provider, self::SUPPORTED_DRIVERS, true)
            && filled(config("services.{$provider}.client_id"));
    }

    private function notConfigured(string $provider): RedirectResponse
    {
        return redirect()->route('login')->withErrors([
            'email' => ucfirst($provider).' sign-in is not yet configured for this deployment.',
        ]);
    }
}
