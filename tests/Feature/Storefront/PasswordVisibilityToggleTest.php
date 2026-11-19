<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;

/**
 * The show/hide toggle itself is applied client-side by JS wrapping every
 * input[type="password"] on the page (see
 * resources/views/partials/password-toggle-script.blade.php) — Pest can't
 * execute that JS to prove the toggle works, but it can guard against the
 * include silently disappearing from a layout later. One page per layout
 * (guest vs. app) that actually has a password field is enough to cover
 * every page sharing that layout.
 */
test('the password visibility toggle script is included on the customer login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('hasVisibilityToggle', false);
});

test('the password visibility toggle script is included on the customer register page', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('hasVisibilityToggle', false);
});

test('the password visibility toggle script is included on the password reset page', function () {
    $user = User::factory()->create();
    $token = Password::broker('customers')->createToken($user);

    $this->get(route('password.reset', $token))
        ->assertOk()
        ->assertSee('hasVisibilityToggle', false);
});

test('the password visibility toggle script is included on the account security page (app layout)', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->get(route('account.security'))
        ->assertOk()
        ->assertSee('hasVisibilityToggle', false);
});

test('the password visibility toggle script is included on the vendor login page', function () {
    $this->get(route('vendor.login'))
        ->assertOk()
        ->assertSee('hasVisibilityToggle', false);
});
