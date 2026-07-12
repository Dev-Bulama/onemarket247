<?php

use App\Models\SocialAccount;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteTwoUser;

beforeEach(function () {
    config(['services.google.client_id' => 'test-client-id']);
});

test('redirect bounces to login when the provider is not configured', function () {
    config(['services.facebook.client_id' => null]);

    $this->get('/auth/social/facebook/redirect')
        ->assertRedirect(route('login'));
});

test('apple is architecture-only and never redirects to a working driver', function () {
    $this->get('/auth/social/apple/redirect')
        ->assertRedirect(route('login'));
});

test('an unrecognized provider is rejected', function () {
    $this->get('/auth/social/not-a-real-provider/redirect')
        ->assertRedirect(route('login'));
});

test('a new customer is created on first Google sign-in', function () {
    Socialite::fake('google', SocialiteTwoUser::fake([
        'id' => 'google-123',
        'name' => 'Social Customer',
        'email' => 'social@example.com',
    ]));

    $response = $this->get('/auth/social/google/callback');

    $user = User::where('email', 'social@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->user_type->value)->toBe('customer')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(SocialAccount::where('provider', 'google')->where('provider_user_id', 'google-123')->exists())->toBeTrue();

    $this->assertAuthenticatedAs($user, 'web');
    $response->assertRedirect(route('account.dashboard'));
});

test('an existing user with a matching email is linked instead of duplicated', function () {
    $existing = User::factory()->create(['email' => 'linked@example.com']);

    Socialite::fake('google', SocialiteTwoUser::fake([
        'id' => 'google-456',
        'email' => 'linked@example.com',
    ]));

    $this->get('/auth/social/google/callback');

    expect(User::where('email', 'linked@example.com')->count())->toBe(1);
    $this->assertAuthenticatedAs($existing, 'web');
});

test('signing in again with the same social account logs in the same user', function () {
    Socialite::fake('google', SocialiteTwoUser::fake([
        'id' => 'google-789',
        'email' => 'repeat@example.com',
    ]));

    $this->get('/auth/social/google/callback');
    auth('web')->logout();

    $this->get('/auth/social/google/callback');

    expect(User::where('email', 'repeat@example.com')->count())->toBe(1)
        ->and(SocialAccount::where('provider_user_id', 'google-789')->count())->toBe(1);
});
