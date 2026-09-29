<?php

use App\Enums\DisclaimerTrigger;
use App\Models\Disclaimer;
use App\Models\DisclaimerAcceptance;
use App\Models\User;

function apiDisclaimerUserToken(): array
{
    $user = User::factory()->create();
    $token = $user->createToken('t', ['customer:*'])->plainTextToken;

    return [$user, $token];
}

test('a guest can fetch the general-browsing disclaimer by guest_id', function () {
    Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::FirstVisit, 'title' => 'Welcome notice']);

    $this->getJson('/api/v1/disclaimers/general?guest_id=guest-1')
        ->assertOk()
        ->assertJsonPath('data.title', 'Welcome notice');
});

test('a logged-in user can fetch the general-browsing disclaimer without a guest_id', function () {
    [, $token] = apiDisclaimerUserToken();
    Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::OncePerUser, 'title' => 'Welcome notice']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/disclaimers/general')
        ->assertOk()
        ->assertJsonPath('data.title', 'Welcome notice');
});

test('fetching a specific trigger returns null once accepted', function () {
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::BeforeCheckout]);

    $this->getJson('/api/v1/disclaimers/active?trigger=before_checkout&guest_id=guest-1')
        ->assertOk()
        ->assertJsonPath('data.id', $disclaimer->id);

    $this->postJson("/api/v1/disclaimers/{$disclaimer->id}/accept", ['guest_id' => 'guest-1'])->assertOk();

    $this->getJson('/api/v1/disclaimers/active?trigger=before_checkout&guest_id=guest-1')
        ->assertOk()
        ->assertJsonPath('data', null);

    expect(DisclaimerAcceptance::where('disclaimer_id', $disclaimer->id)->where('guest_identifier', 'guest-1')->count())->toBe(1);
});

test('accepting as a logged-in user records against their account, not a guest_id', function () {
    [$user, $token] = apiDisclaimerUserToken();
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::OncePerUser]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/disclaimers/{$disclaimer->id}/accept")
        ->assertOk();

    expect(DisclaimerAcceptance::where('disclaimer_id', $disclaimer->id)->where('user_id', $user->id)->count())->toBe(1)
        ->and(DisclaimerAcceptance::whereNotNull('guest_identifier')->count())->toBe(0);
});

test('an invalid trigger value is rejected', function () {
    $this->getJson('/api/v1/disclaimers/active?trigger=not-a-real-trigger&guest_id=guest-1')
        ->assertStatus(422);
});
