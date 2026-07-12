<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorAuthenticationService;
use App\Support\Auth\TwoFactorSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    public function __construct(private readonly TwoFactorAuthenticationService $twoFactor) {}

    public function create(): View|RedirectResponse
    {
        if (! TwoFactorSession::pending()) {
            return redirect()->route('login');
        }

        return view('auth.two-factor.challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $pending = TwoFactorSession::pending();

        if (! $pending) {
            return redirect()->route('login');
        }

        $request->validate(['code' => ['required', 'string']]);

        $user = User::findOrFail($pending['user_id']);
        $credential = $user->twoFactorCredential;

        $codeIsValid = $credential && $this->twoFactor->verify($credential->secret, $request->string('code'));
        $recoveryCodeUsed = false;

        if (! $codeIsValid && $credential) {
            $recoveryCodes = $credential->recovery_codes ?? [];
            $recoveryCodeUsed = in_array($request->string('code')->upper()->value(), $recoveryCodes, true);

            if ($recoveryCodeUsed) {
                $credential->update([
                    'recovery_codes' => array_values(array_diff($recoveryCodes, [$request->string('code')->upper()->value()])),
                ]);
            }
        }

        if (! $codeIsValid && ! $recoveryCodeUsed) {
            throw ValidationException::withMessages(['code' => 'That code is invalid.']);
        }

        Auth::guard($pending['guard'])->login($user, $pending['remember']);
        $request->session()->regenerate();
        TwoFactorSession::clear();

        return redirect()->intended($this->redirectFor($pending['guard']));
    }

    private function redirectFor(string $guard): string
    {
        return match ($guard) {
            'admin' => route('filament.admin.pages.dashboard'),
            'vendor' => route('vendor.dashboard'),
            default => route('account.dashboard'),
        };
    }
}
