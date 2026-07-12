<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

test('a customer can register and is redirected toward email verification', function () {
    Notification::fake();

    $response = $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!',
    ]);

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->user_type->value)->toBe('customer')
        ->and($user->customerProfile)->not->toBeNull();

    $this->assertAuthenticatedAs($user, 'web');
    $response->assertRedirect(route('account.dashboard'));
});

test('registration requires a unique email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'taken@example.com',
        'password' => 'Sup3rSecret!',
        'password_confirmation' => 'Sup3rSecret!',
    ]);

    $response->assertSessionHasErrors('email');
});

test('registration requires matching password confirmation', function () {
    $response = $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Sup3rSecret!',
        'password_confirmation' => 'DifferentPassword!',
    ]);

    $response->assertSessionHasErrors('password');
});
