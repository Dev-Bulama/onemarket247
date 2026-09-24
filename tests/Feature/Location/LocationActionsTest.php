<?php

use App\Actions\Location\PruneLocationHistoryAction;
use App\Actions\Location\RecordLocationPingAction;
use App\Actions\Location\UpdateLocationConsentAction;
use App\Exceptions\LocationSharingDisabledException;
use App\Models\LocationPing;
use App\Models\Setting;
use App\Models\User;

test('enabling location consent records the enabled timestamp and clears any disabled timestamp', function () {
    $user = User::factory()->create();

    $consent = app(UpdateLocationConsentAction::class)->handle($user, true);

    expect($consent->is_enabled)->toBeTrue()
        ->and($consent->enabled_at)->not->toBeNull()
        ->and($consent->disabled_at)->toBeNull();
});

test('disabling location consent records the disabled timestamp', function () {
    $user = User::factory()->create();
    app(UpdateLocationConsentAction::class)->handle($user, true);

    $consent = app(UpdateLocationConsentAction::class)->handle($user, false);

    expect($consent->is_enabled)->toBeFalse()
        ->and($consent->disabled_at)->not->toBeNull();
});

test('a ping is rejected when the user has never enabled location sharing', function () {
    $user = User::factory()->create();

    expect(fn () => app(RecordLocationPingAction::class)->handle($user, 6.5, 3.4))
        ->toThrow(LocationSharingDisabledException::class);

    expect(LocationPing::where('user_id', $user->id)->count())->toBe(0);
});

test('a ping is rejected once sharing has been disabled again', function () {
    $user = User::factory()->create();
    app(UpdateLocationConsentAction::class)->handle($user, true);
    app(UpdateLocationConsentAction::class)->handle($user, false);

    expect(fn () => app(RecordLocationPingAction::class)->handle($user, 6.5, 3.4))
        ->toThrow(LocationSharingDisabledException::class);
});

test('a ping is recorded once sharing is enabled', function () {
    $user = User::factory()->create();
    app(UpdateLocationConsentAction::class)->handle($user, true);

    $ping = app(RecordLocationPingAction::class)->handle($user, 6.5244, 3.3792, 12.5);

    expect((float) $ping->latitude)->toBe(6.5244)
        ->and((float) $ping->longitude)->toBe(3.3792)
        ->and($ping->accuracy)->toBe(12.5);
});

test('latestPerUser only returns the most recent ping for each user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    LocationPing::factory()->create(['user_id' => $userA->id, 'recorded_at' => now()->subMinutes(10)]);
    $latestA = LocationPing::factory()->create(['user_id' => $userA->id, 'recorded_at' => now()]);
    $latestB = LocationPing::factory()->create(['user_id' => $userB->id, 'recorded_at' => now()]);

    $results = LocationPing::latestPerUser()->get();

    expect($results->pluck('id')->sort()->values()->all())
        ->toBe(collect([$latestA->id, $latestB->id])->sort()->values()->all());
});

test('pruning deletes pings past the retention window and keeps recent ones', function () {
    Setting::updateOrCreate(['key' => 'location.history_retention_days'], ['value' => '7', 'type' => 'integer', 'group' => 'location']);
    $user = User::factory()->create();

    $old = LocationPing::factory()->create(['user_id' => $user->id, 'recorded_at' => now()->subDays(10)]);
    $recent = LocationPing::factory()->create(['user_id' => $user->id, 'recorded_at' => now()->subDays(1)]);

    $deleted = app(PruneLocationHistoryAction::class)->handle();

    expect($deleted)->toBe(1);
    expect(LocationPing::find($old->id))->toBeNull();
    expect(LocationPing::find($recent->id))->not->toBeNull();
});

test('pruning falls back to the default retention window when unconfigured', function () {
    $user = User::factory()->create();
    $old = LocationPing::factory()->create(['user_id' => $user->id, 'recorded_at' => now()->subDays(40)]);
    $recent = LocationPing::factory()->create(['user_id' => $user->id, 'recorded_at' => now()->subDays(5)]);

    app(PruneLocationHistoryAction::class)->handle();

    expect(LocationPing::find($old->id))->toBeNull();
    expect(LocationPing::find($recent->id))->not->toBeNull();
});
