<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TwoFactorCredential;
use App\Services\Auth\TwoFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorAuthenticationController extends Controller
{
    public function __construct(private readonly TwoFactorAuthenticationService $twoFactor) {}

    public function create(Request $request): View
    {
        $user = $request->user();

        // Query fresh rather than the (possibly stale-cached) relation
        // property, since $user may be a long-lived instance shared across
        // requests within the same process (e.g. queued jobs, tests).
        $credential = $user->twoFactorCredential()->first();

        if ($credential && $credential->isConfirmed()) {
            return view('auth.two-factor.enabled');
        }

        if (! $credential) {
            $credential = TwoFactorCredential::create([
                'user_id' => $user->id,
                'secret' => $this->twoFactor->generateSecret(),
            ]);
        }

        return view('auth.two-factor.setup', [
            'qrCodeSvg' => $this->twoFactor->qrCodeSvg($user, $credential->secret),
            'secret' => $credential->secret,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $user = $request->user();
        $credential = $user->twoFactorCredential()->first();

        if (! $credential || ! $this->twoFactor->verify($credential->secret, $request->string('code'))) {
            return back()->withErrors(['code' => 'That code is invalid or has expired.']);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $credential->update([
            'confirmed_at' => now(),
            'recovery_codes' => $recoveryCodes,
        ]);

        return redirect()
            ->route('two-factor.show')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password:web']]);

        $request->user()->twoFactorCredential()->delete();

        return redirect()->route('two-factor.show')->with('status', 'Two-factor authentication disabled.');
    }
}
