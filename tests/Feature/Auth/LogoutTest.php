<?php

use App\Models\User;

test('an authenticated customer can log out', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'web')->post('/logout');

    $this->assertGuest('web');
    $response->assertRedirect(route('home'));
});
