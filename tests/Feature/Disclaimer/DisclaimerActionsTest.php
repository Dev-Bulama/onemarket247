<?php

use App\Actions\Disclaimer\RecordDisclaimerAcceptanceAction;
use App\Actions\Disclaimer\ResolveActiveDisclaimerAction;
use App\Enums\DisclaimerTrigger;
use App\Models\Disclaimer;
use App\Models\DisclaimerAcceptance;
use App\Models\User;
use Illuminate\Http\Request;

test('an active disclaimer for its trigger is returned when unaccepted', function () {
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::BeforeCheckout]);

    $resolved = app(ResolveActiveDisclaimerAction::class)->handle(DisclaimerTrigger::BeforeCheckout, null, 'guest-1');

    expect($resolved?->id)->toBe($disclaimer->id);
});

test('an inactive disclaimer is never resolved', function () {
    Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::BeforeCheckout, 'is_active' => false]);

    expect(app(ResolveActiveDisclaimerAction::class)->handle(DisclaimerTrigger::BeforeCheckout, null, 'guest-1'))->toBeNull();
});

test('a disclaimer outside its scheduling window is never resolved', function () {
    Disclaimer::factory()->create([
        'trigger' => DisclaimerTrigger::BeforeCheckout,
        'start_at' => now()->addDay(),
    ]);
    Disclaimer::factory()->create([
        'trigger' => DisclaimerTrigger::BeforeCheckout,
        'end_at' => now()->subDay(),
    ]);

    expect(app(ResolveActiveDisclaimerAction::class)->handle(DisclaimerTrigger::BeforeCheckout, null, 'guest-1'))->toBeNull();
});

test('forGeneralBrowsing returns whichever general-trigger disclaimer is available', function () {
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::OncePerUser]);

    $resolved = app(ResolveActiveDisclaimerAction::class)->forGeneralBrowsing(null, 'guest-1');

    expect($resolved?->id)->toBe($disclaimer->id);
});

test('recording acceptance for a logged-in user is idempotent', function () {
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::OncePerUser]);
    $user = User::factory()->create();
    $request = Request::create('/');

    app(RecordDisclaimerAcceptanceAction::class)->handle($disclaimer, $user, null, $request);
    app(RecordDisclaimerAcceptanceAction::class)->handle($disclaimer, $user, null, $request);

    expect(DisclaimerAcceptance::where('disclaimer_id', $disclaimer->id)->where('user_id', $user->id)->count())->toBe(1);
    expect(app(ResolveActiveDisclaimerAction::class)->handle(DisclaimerTrigger::OncePerUser, $user, null))->toBeNull();
});

test('recording acceptance for a guest is idempotent and keyed on their identifier', function () {
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::FirstVisit]);
    $request = Request::create('/');

    app(RecordDisclaimerAcceptanceAction::class)->handle($disclaimer, null, 'guest-abc', $request);
    app(RecordDisclaimerAcceptanceAction::class)->handle($disclaimer, null, 'guest-abc', $request);

    expect(DisclaimerAcceptance::where('disclaimer_id', $disclaimer->id)->where('guest_identifier', 'guest-abc')->count())->toBe(1);
    expect(app(ResolveActiveDisclaimerAction::class)->handle(DisclaimerTrigger::FirstVisit, null, 'guest-abc'))->toBeNull();

    // A different guest hasn't accepted it.
    expect(app(ResolveActiveDisclaimerAction::class)->handle(DisclaimerTrigger::FirstVisit, null, 'guest-xyz'))->not->toBeNull();
});

test('a once_per_session acceptance never writes to the database', function () {
    $disclaimer = Disclaimer::factory()->create(['trigger' => DisclaimerTrigger::OncePerSession]);

    $this->post(route('disclaimers.accept', $disclaimer));

    expect(DisclaimerAcceptance::count())->toBe(0);
});
