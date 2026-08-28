<?php

use App\Models\User;
use App\Notifications\AdminBroadcastNotification;

test('a customer can list their notifications and mark one read', function () {
    $customer = User::factory()->create();
    $token = $customer->createToken('t', ['customer:*'])->plainTextToken;

    $customer->notify(new AdminBroadcastNotification('Welcome!', 'Thanks for joining.'));
    $notificationId = $customer->notifications()->first()->id;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('data.0.subject', 'Welcome!')
        ->assertJsonPath('data.0.read_at', null);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/notifications/{$notificationId}/read")
        ->assertOk()
        ->assertJsonPath('data.subject', 'Welcome!');

    expect($customer->notifications()->first()->read_at)->not->toBeNull();
});

test('notifications require authentication', function () {
    $this->getJson('/api/v1/notifications')->assertUnauthorized();
});

test('a customer cannot mark another customer\'s notification as read', function () {
    $owner = User::factory()->create();
    $owner->notify(new AdminBroadcastNotification('Private', 'Just for the owner.'));
    $notificationId = $owner->notifications()->first()->id;

    $intruder = User::factory()->create();
    $token = $intruder->createToken('t', ['customer:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/notifications/{$notificationId}/read")
        ->assertNotFound();
});
