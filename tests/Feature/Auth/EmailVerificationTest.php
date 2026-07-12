<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

test('an unverified user is redirected to the verification prompt from a verified-only route', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user, 'web')->get('/account');

    $response->assertRedirect(route('verification.notice'));
});

test('a valid signed link verifies the email', function () {
    Event::fake();

    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $response = $this->actingAs($user, 'web')->get($url);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
    $response->assertRedirect();
});

test('an invalid signature is rejected', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user, 'web')->get("/verify-email/{$user->id}/".sha1('wrong-hash').'?expires=9999999999&signature=invalid');

    $response->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
