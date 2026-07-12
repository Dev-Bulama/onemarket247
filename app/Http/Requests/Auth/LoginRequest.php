<?php

namespace App\Http\Requests\Auth;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Validate credentials against the given guard WITHOUT logging the user
     * in, so the caller can branch into a 2FA challenge first. Enforces
     * throttling and the account-status gate — see
     * docs/architecture/10-security-architecture.md §1.
     */
    public function resolveUser(string $guard = 'web'): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->string('email'))->first();

        if ($user && in_array($user->status->value, ['suspended', 'banned'], true)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'This account has been '.$user->status->value.'. Contact support for help.',
            ]);
        }

        $provider = Auth::guard($guard)->getProvider();
        $credentials = $this->only('email', 'password');
        $resolved = $provider->retrieveByCredentials($credentials);

        if (! $resolved || ! $provider->validateCredentials($resolved, $credentials)) {
            RateLimiter::hit($this->throttleKey());

            if ($user) {
                LoginHistory::create([
                    'user_id' => $user->id,
                    'guard' => $guard,
                    'ip_address' => $this->ip(),
                    'user_agent' => (string) $this->userAgent(),
                    'device_fingerprint' => sha1($this->ip().'|'.$this->userAgent()),
                    'is_new_device' => false,
                    'successful' => false,
                ]);
            }

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $resolved;
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('Too many login attempts. Please try again in :seconds seconds.', [
                'seconds' => $seconds,
            ]),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
