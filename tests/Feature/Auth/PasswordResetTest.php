<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

test('requesting a reset link sends a notification with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('a user can reset their password with a valid token', function () {
    $user = User::factory()->create(['password' => bcrypt('OldPassword!')]);

    $token = Password::broker('customers')->createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword!23',
        'password_confirmation' => 'NewPassword!23',
    ]);

    $response->assertRedirect(route('login'));

    $this->assertTrue(Hash::check('NewPassword!23', $user->fresh()->password));
});

test('an invalid token is rejected', function () {
    $user = User::factory()->create();

    $response = $this->post('/reset-password', [
        'token' => 'not-a-real-token',
        'email' => $user->email,
        'password' => 'NewPassword!23',
        'password_confirmation' => 'NewPassword!23',
    ]);

    $response->assertSessionHasErrors('email');
});
