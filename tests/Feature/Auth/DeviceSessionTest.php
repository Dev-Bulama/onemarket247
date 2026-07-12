<?php

use App\Models\DeviceSession;
use App\Models\User;

test('logging in records a device session', function () {
    $user = User::factory()->create(['password' => bcrypt('Sup3rSecret!')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'Sup3rSecret!']);

    expect(DeviceSession::where('user_id', $user->id)->exists())->toBeTrue();
});

test('a user can revoke a specific device session', function () {
    $user = User::factory()->create();
    $session = DeviceSession::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user, 'web')->delete("/account/sessions/{$session->id}");

    $response->assertRedirect();
    expect(DeviceSession::find($session->id))->toBeNull();
});

test('a user cannot revoke another user\'s device session', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $session = DeviceSession::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($stranger, 'web')->delete("/account/sessions/{$session->id}")
        ->assertForbidden();

    expect(DeviceSession::find($session->id))->not->toBeNull();
});
