<?php

namespace App\Listeners\Auth;

use App\Models\DeviceSession;
use App\Models\LoginHistory;
use App\Notifications\SuspiciousLoginNotification;
use Illuminate\Auth\Events\Login;
use Throwable;

/**
 * Fires for every guard (admin/vendor/web) since Laravel dispatches
 * Illuminate\Auth\Events\Login regardless of which guard authenticated —
 * this is the single place login history, device sessions, and the
 * suspicious-login alert are recorded, per
 * docs/architecture/10-security-architecture.md §1.
 *
 * Runs synchronously (not queued): it reads the live request/session, which
 * would not exist by the time a queued job executed. SuspiciousLoginNotification
 * itself is Queueable, but that only actually defers sending when
 * QUEUE_CONNECTION is a real async driver with a worker running — with the
 * "sync" driver (or no worker), a queued job still runs inline. Either way,
 * a login itself must never fail because the alert email couldn't be sent.
 */
class RecordLoginActivity
{
    public function handle(Login $event): void
    {
        $request = request();
        $fingerprint = sha1($request->ip().'|'.$request->userAgent());

        $hadPriorLogins = LoginHistory::where('user_id', $event->user->id)->exists();
        $isNewDevice = $hadPriorLogins && ! LoginHistory::where('user_id', $event->user->id)
            ->where('device_fingerprint', $fingerprint)
            ->exists();

        $loginHistory = LoginHistory::create([
            'user_id' => $event->user->id,
            'guard' => $event->guard,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'device_fingerprint' => $fingerprint,
            'is_new_device' => $isNewDevice,
            'successful' => true,
        ]);

        if ($request->hasSession()) {
            DeviceSession::updateOrCreate(
                ['session_id' => $request->session()->getId()],
                [
                    'user_id' => $event->user->id,
                    'guard' => $event->guard,
                    'device_fingerprint' => $fingerprint,
                    'ip_address' => $request->ip(),
                    'user_agent' => (string) $request->userAgent(),
                    'last_used_at' => now(),
                ],
            );
        }

        if ($isNewDevice) {
            try {
                $event->user->notify(new SuspiciousLoginNotification($loginHistory));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
