<?php

use App\Models\LocationConsent;
use App\Models\User;

function apiUserToken(): array
{
    $user = User::factory()->create();
    $token = $user->createToken('t', ['customer:*'])->plainTextToken;

    return [$user, $token];
}

test('an unauthenticated request cannot update location consent or submit a ping', function () {
    $this->postJson('/api/v1/location/consent', ['enabled' => true])->assertUnauthorized();
    $this->postJson('/api/v1/location/ping', ['latitude' => 6.5, 'longitude' => 3.4])->assertUnauthorized();
});

test('a logged-in user can enable location sharing via the API', function () {
    [$user, $token] = apiUserToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/location/consent', ['enabled' => true])
        ->assertOk()
        ->assertJsonPath('data.enabled', true);

    expect(LocationConsent::where('user_id', $user->id)->first()?->is_enabled)->toBeTrue();
});

test('submitting a ping without having enabled sharing is rejected', function () {
    [, $token] = apiUserToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/location/ping', ['latitude' => 6.5, 'longitude' => 3.4])
        ->assertUnprocessable()
        ->assertJsonPath('error_code', 'LOCATION_SHARING_DISABLED');
});

test('submitting a ping after enabling sharing succeeds', function () {
    [, $token] = apiUserToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/location/consent', ['enabled' => true])
        ->assertOk();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/location/ping', ['latitude' => 6.5244, 'longitude' => 3.3792, 'accuracy' => 10])
        ->assertCreated();
});

test('a ping outside valid latitude/longitude ranges is rejected', function () {
    [, $token] = apiUserToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/location/ping', ['latitude' => 200, 'longitude' => 3.4])
        ->assertUnprocessable();
});
