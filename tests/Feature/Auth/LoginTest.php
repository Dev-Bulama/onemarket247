<?php

use App\Enums\UserStatus;
use App\Models\LoginHistory;
use App\Models\User;

test('a customer can log in with correct credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('Sup3rSecret!')]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'Sup3rSecret!',
    ]);

    $this->assertAuthenticatedAs($user, 'web');
    $response->assertRedirect(route('account.dashboard'));

    expect(LoginHistory::where('user_id', $user->id)->where('successful', true)->exists())->toBeTrue();
});

test('login fails with the wrong password and records a failed attempt', function () {
    $user = User::factory()->create(['password' => bcrypt('Sup3rSecret!')]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'WrongPassword',
    ]);

    $this->assertGuest('web');
    $response->assertSessionHasErrors('email');

    expect(LoginHistory::where('user_id', $user->id)->where('successful', false)->exists())->toBeTrue();
});

test('a suspended account cannot log in even with the correct password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('Sup3rSecret!'),
        'status' => UserStatus::Suspended,
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'Sup3rSecret!',
    ]);

    $this->assertGuest('web');
    $response->assertSessionHasErrors('email');
});

test('login is rate limited after five failed attempts', function () {
    $user = User::factory()->create(['password' => bcrypt('Sup3rSecret!')]);

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $response = $this->post('/login', ['email' => $user->email, 'password' => 'Sup3rSecret!']);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('web');
});
