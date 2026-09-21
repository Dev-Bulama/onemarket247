<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('vendor.auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker('vendors')->sendResetLink(
            $request->only('email'),
        );

        // Only a genuine throttle is surfaced as an error. An unknown email
        // (Password::INVALID_USER) gets the exact same generic success
        // message as a real send, so a prober can never learn whether a
        // given address has a vendor account from this form's response.
        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors(['email' => __($status)]);
        }

        return back()->with('status', __(Password::RESET_LINK_SENT));
    }
}
