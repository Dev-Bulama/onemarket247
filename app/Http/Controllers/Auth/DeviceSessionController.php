<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\DeviceSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The device session list itself is rendered by AccountController::security();
 * this controller only handles the mutating revoke actions.
 */
class DeviceSessionController extends Controller
{
    public function destroy(Request $request, DeviceSession $deviceSession): RedirectResponse
    {
        abort_unless($deviceSession->user_id === $request->user()->id, 403);

        $deviceSession->delete();

        return back()->with('status', 'session-revoked');
    }

    /**
     * Log out of every device except the one making this request, using
     * Laravel's built-in per-guard session invalidation (requires the
     * "auth.session" middleware on protected routes to take effect on the
     * next request from those other sessions) — this is the actual security
     * enforcement; device_sessions rows below are only our display/audit
     * layer and are cleaned up to match.
     */
    public function destroyOthers(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password:web']]);

        Auth::guard('web')->logoutOtherDevices($request->string('password'));

        $request->user()->deviceSessions()
            ->where('session_id', '!=', $request->session()->getId())
            ->delete();

        return back()->with('status', 'other-sessions-revoked');
    }
}
