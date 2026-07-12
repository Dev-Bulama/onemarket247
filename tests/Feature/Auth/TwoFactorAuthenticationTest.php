<?php

use App\Models\User;
use App\Support\Auth\TwoFactorSession;
use PragmaRX\Google2FA\Google2FA;

function currentOtpFor(string $secret): string
{
    return app(Google2FA::class)->getCurrentOtp($secret);
}

test('a user can enable two-factor authentication with a valid code', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'web')->get('/two-factor-authentication');

    $secret = $user->fresh()->twoFactorCredential->secret;

    $response = $this->actingAs($user, 'web')->post('/two-factor-authentication', [
        'code' => currentOtpFor($secret),
    ]);

    $response->assertRedirect(route('two-factor.show'));
    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

test('enabling two-factor authentication rejects an invalid code', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'web')->get('/two-factor-authentication');

    $response = $this->actingAs($user, 'web')->post('/two-factor-authentication', ['code' => '000000']);

    $response->assertSessionHasErrors('code');
    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

test('login with two-factor enabled requires a challenge before establishing a session', function () {
    $user = User::factory()->create(['password' => bcrypt('Sup3rSecret!')]);
    $this->actingAs($user, 'web')->get('/two-factor-authentication');
    $secret = $user->fresh()->twoFactorCredential->secret;
    $this->actingAs($user, 'web')->post('/two-factor-authentication', ['code' => currentOtpFor($secret)]);
    auth('web')->logout();

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'Sup3rSecret!']);

    $this->assertGuest('web');
    $response->assertRedirect(route('two-factor.challenge'));

    $challengeResponse = $this->post('/two-factor-challenge', ['code' => currentOtpFor($secret)]);

    $this->assertAuthenticatedAs($user, 'web');
    $challengeResponse->assertRedirect(route('account.dashboard'));
});

test('the two-factor challenge rejects an invalid code', function () {
    $user = User::factory()->create();
    TwoFactorSession::stash($user->id, 'web', false);
    $user->twoFactorCredential()->create(['secret' => 'ABCDEFGHIJKLMNOP', 'confirmed_at' => now()]);

    $response = $this->post('/two-factor-challenge', ['code' => '000000']);

    $this->assertGuest('web');
    $response->assertSessionHasErrors('code');
});
