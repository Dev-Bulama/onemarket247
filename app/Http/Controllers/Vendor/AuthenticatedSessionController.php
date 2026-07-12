<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Auth\TwoFactorSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('vendor.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->resolveUser('vendor');

        // Vendor staff accounts have no Vendor record of their own; only the
        // owning Vendor's status gates dashboard access — see
        // docs/architecture/07-vendor-dashboard.md §3.
        $vendor = $user->vendor;

        if ($vendor && ! $vendor->canAccessDashboard()) {
            throw ValidationException::withMessages([
                'email' => 'Your store account is '.$vendor->status->getLabel().' and cannot access the dashboard right now.',
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            TwoFactorSession::stash($user->id, 'vendor', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        Auth::guard('vendor')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('vendor.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('vendor')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login');
    }
}
