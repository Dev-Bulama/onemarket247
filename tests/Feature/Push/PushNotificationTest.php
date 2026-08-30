<?php

use App\Models\DeviceToken;
use App\Models\PushSetting;
use App\Models\User;
use App\Notifications\AdminBroadcastNotification;
use App\Notifications\Channels\OneSignalChannel;
use App\Notifications\Messages\OneSignalMessage;
use Illuminate\Support\Facades\Http;

test('a device token can be registered for the authenticated user', function () {
    [$user, $token] = apiCustomerToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/device-tokens', ['token' => 'onesignal-player-id-1', 'platform' => 'android'])
        ->assertCreated();

    expect(DeviceToken::where('token', 'onesignal-player-id-1')->first()?->user_id)->toBe($user->id);
});

test('registering an existing token reassigns it to the new user instead of crashing', function () {
    $original = User::factory()->create();
    DeviceToken::create(['user_id' => $original->id, 'token' => 'shared-device', 'platform' => 'ios']);

    [$newUser, $token] = apiCustomerToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/device-tokens', ['token' => 'shared-device'])
        ->assertCreated();

    expect(DeviceToken::where('token', 'shared-device')->count())->toBe(1)
        ->and(DeviceToken::where('token', 'shared-device')->first()->user_id)->toBe($newUser->id);
});

test('a device token can be unregistered', function () {
    [$user, $token] = apiCustomerToken();
    DeviceToken::create(['user_id' => $user->id, 'token' => 'to-remove']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson('/api/v1/device-tokens', ['token' => 'to-remove'])
        ->assertOk();

    expect(DeviceToken::where('token', 'to-remove')->exists())->toBeFalse();
});

test('OneSignalChannel does nothing when push settings are inactive', function () {
    Http::fake();
    PushSetting::current()->update(['is_active' => false, 'app_id' => 'app', 'rest_api_key' => 'key']);
    $user = User::factory()->create();
    DeviceToken::create(['user_id' => $user->id, 'token' => 'device-1']);

    $user->notify(new AdminBroadcastNotification('Subject', 'Body'));

    Http::assertNothingSent();
});

test('OneSignalChannel does nothing when the user has no registered device', function () {
    Http::fake();
    PushSetting::current()->update(['is_active' => true, 'app_id' => 'app', 'rest_api_key' => 'key']);
    $user = User::factory()->create();

    $user->notify(new AdminBroadcastNotification('Subject', 'Body'));

    Http::assertNothingSent();
});

test('OneSignalChannel sends to every registered device when active and configured', function () {
    Http::fake(['onesignal.com/*' => Http::response(['id' => 'abc'], 200)]);
    PushSetting::current()->update(['is_active' => true, 'app_id' => 'my-app-id', 'rest_api_key' => 'my-key']);
    $user = User::factory()->create();
    DeviceToken::create(['user_id' => $user->id, 'token' => 'device-1']);
    DeviceToken::create(['user_id' => $user->id, 'token' => 'device-2']);

    $user->notify(new AdminBroadcastNotification('Big Sale', 'Everything is 20% off today.'));

    Http::assertSent(function ($request) {
        return $request->url() === 'https://onesignal.com/api/v1/notifications'
            && $request['app_id'] === 'my-app-id'
            && $request['include_player_ids'] === ['device-1', 'device-2']
            && $request['headings']['en'] === 'Big Sale'
            && $request['contents']['en'] === 'Everything is 20% off today.'
            && $request->hasHeader('Authorization', 'Basic my-key');
    });
});

test('a push failure does not prevent the mail and database channels from delivering', function () {
    Http::fake(['onesignal.com/*' => Http::response(['errors' => ['Invalid app_id']], 400)]);
    PushSetting::current()->update(['is_active' => true, 'app_id' => 'bad-app-id', 'rest_api_key' => 'my-key']);
    $user = User::factory()->create();
    DeviceToken::create(['user_id' => $user->id, 'token' => 'device-1']);

    $user->notify(new AdminBroadcastNotification('Subject', 'Body'));

    expect($user->notifications()->count())->toBe(1);
});

test('OneSignalChannel::sendRaw throws on failure, unlike the defensive send() used for live notifications', function () {
    Http::fake(['onesignal.com/*' => Http::response(['errors' => ['bad']], 400)]);

    expect(fn () => OneSignalChannel::sendRaw('app', 'key', ['device-1'], OneSignalMessage::create('T', 'B')))
        ->toThrow(Exception::class);
});
