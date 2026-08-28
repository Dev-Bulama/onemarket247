<?php

use App\Models\LoginHistory;
use App\Models\User;

/**
 * Reproduces the exact crash reported in production: a new-device login
 * fires SuspiciousLoginNotification synchronously, and when the mail
 * transport is unreachable (127.0.0.1:2525 refused, or any other
 * misconfiguration) the whole login request 500'd instead of just
 * failing to send the alert email. Points the mailer at a real,
 * immediately-refused local port rather than mocking the exception, so
 * this proves the actual HTTP request survives, not just the listener.
 */
test('a new-device login succeeds even when the mail transport is unreachable', function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
    ]);

    $user = User::factory()->create(['password' => bcrypt('password')]);

    // A prior login from a different device establishes "this is a new
    // device" for the login below — SuspiciousLoginNotification only
    // fires on the second, different-fingerprint login.
    LoginHistory::factory()->create([
        'user_id' => $user->id,
        'device_fingerprint' => 'a-completely-different-fingerprint',
    ]);

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('account.dashboard'));
    $this->assertAuthenticatedAs($user, 'web');
});
