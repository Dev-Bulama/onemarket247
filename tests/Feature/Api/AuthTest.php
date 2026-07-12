<?php

use App\Models\User;

test('a customer can register via the API and receives a scoped token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'API Customer',
        'email' => 'apicustomer@example.com',
        'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.user.user_type', 'customer');

    $user = User::where('email', 'apicustomer@example.com')->first();
    expect($user->tokens()->first()->abilities)->toBe(['customer:*']);
});

test('a customer can log in via the API and the token can access protected endpoints', function () {
    $user = User::factory()->create(['password' => bcrypt('Sup3rSecret!')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'Sup3rSecret!',
        'device_name' => 'test-device',
    ]);

    $response->assertOk();
    $token = $response->json('data.token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/sessions')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('a vendor user receives a vendor-scoped token', function () {
    $vendorUser = User::factory()->vendorOwner()->create(['password' => bcrypt('Sup3rSecret!')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $vendorUser->email,
        'password' => 'Sup3rSecret!',
        'device_name' => 'test-device',
    ]);

    $response->assertOk();
    expect($vendorUser->tokens()->first()->abilities)->toBe(['vendor:*']);
});

test('administrators cannot authenticate through the API', function () {
    $admin = User::factory()->admin()->create(['password' => bcrypt('Sup3rSecret!')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $admin->email,
        'password' => 'Sup3rSecret!',
        'device_name' => 'test-device',
    ]);

    $response->assertStatus(422);
});

test('logging out revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test', ['customer:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect($user->tokens()->count())->toBe(0);

    // Sanctum's guard caches the resolved user for the lifetime of the
    // container; forgetGuards() forces re-resolution so this simulated
    // second request re-validates the (now-deleted) token, matching what
    // a real second HTTP request would do.
    auth()->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/sessions')
        ->assertUnauthorized();
});

test('an unauthenticated request to a protected endpoint returns a consistent JSON envelope', function () {
    $this->getJson('/api/v1/auth/sessions')
        ->assertUnauthorized()
        ->assertJsonPath('error_code', 'UNAUTHENTICATED');
});

test('a user can revoke one of their own tokens by id', function () {
    $user = User::factory()->create();
    $activeToken = $user->createToken('active', ['customer:*'])->plainTextToken;
    $otherToken = $user->createToken('other', ['customer:*']);

    $this->withHeader('Authorization', "Bearer {$activeToken}")
        ->deleteJson("/api/v1/auth/sessions/{$otherToken->accessToken->id}")
        ->assertOk();

    expect($user->tokens()->count())->toBe(1);
});
