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

        // Covers both a suspended vendor owner and a staff member whose
        // owning store's vendor is suspended — see User::canAccessVendorDashboard()
        // and docs/architecture/07-vendor-dashboard.md §3. The message is
        // specific to *why* (pending review, suspended, rejected,
        // deactivated, banned) — see User::vendorLoginDeniedMessage().
        if (! $user->canAccessVendorDashboard()) {
            throw ValidationException::withMessages([
                'email' => $user->vendorLoginDeniedMessage(),
            ]);
        }

        if ($user->hasTwoFactorEnabled()) {
            TwoFactorSession::stash($user->id, 'vendor', $request->boolean('remember'));

            return redirect()->route('two-factor.challenge');
        }

        Auth::guard('vendor')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('filament.vendor.pages.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('vendor')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('vendor.login');
    }
}
