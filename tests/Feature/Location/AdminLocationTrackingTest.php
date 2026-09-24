<?php

use App\Actions\Location\RecordLocationPingAction;
use App\Actions\Location\UpdateLocationConsentAction;
use App\Models\AuditLog;
use App\Models\LocationPing;
use App\Models\User;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

function adminWithLocationPermission(): User
{
    $admin = User::factory()->admin()->create();
    $admin->givePermissionTo(Permission::findOrCreate('location_tracking.view', 'admin'));

    return $admin;
}

function sharingUser(float $lat = 6.5244, float $lng = 3.3792, ?Carbon $recordedAt = null): User
{
    $user = User::factory()->create();
    app(UpdateLocationConsentAction::class)->handle($user, true);
    app(RecordLocationPingAction::class)->handle($user, $lat, $lng);

    if ($recordedAt) {
        LocationPing::where('user_id', $user->id)->update(['recorded_at' => $recordedAt]);
    }

    return $user;
}

test('a request without the location_tracking.view permission is forbidden', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.location-tracking.data'))
        ->assertForbidden();
});

test('the data endpoint only returns users who have enabled location sharing', function () {
    $admin = adminWithLocationPermission();
    $sharing = sharingUser();
    $notSharing = User::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.location-tracking.data'))
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($sharing->id)
        ->and($ids)->not->toContain($notSharing->id);
});

test('the online_only filter excludes stale locations', function () {
    $admin = adminWithLocationPermission();
    $online = sharingUser(recordedAt: now());
    $offline = sharingUser(recordedAt: now()->subHours(2));

    $response = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.location-tracking.data', ['online_only' => 1]))
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($online->id)
        ->and($ids)->not->toContain($offline->id);
});

test('the type filter scopes results to one user type', function () {
    $admin = adminWithLocationPermission();
    $customer = sharingUser();
    $vendorOwnerUser = User::factory()->vendorOwner()->create();
    app(UpdateLocationConsentAction::class)->handle($vendorOwnerUser, true);
    app(RecordLocationPingAction::class)->handle($vendorOwnerUser, 6.5, 3.4);

    $response = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.location-tracking.data', ['type' => 'vendor_owner']))
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('id');
    expect($ids)->toContain($vendorOwnerUser->id)
        ->and($ids)->not->toContain($customer->id);
});

test('viewing a user\'s location history returns their pings and writes an audit log entry', function () {
    $admin = adminWithLocationPermission();
    $user = sharingUser();

    $response = $this->actingAs($admin, 'admin')
        ->getJson(route('admin.location-tracking.history', $user))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);

    expect(AuditLog::where('action', 'location.history_viewed')
        ->where('auditable_id', $user->id)
        ->where('user_id', $admin->id)
        ->exists())->toBeTrue();
});

test('loading the live location tracking page writes an audit log entry', function () {
    $admin = adminWithLocationPermission();

    $this->actingAs($admin, 'admin')->get('/admin/live-location-tracking')->assertOk();

    expect(AuditLog::where('action', 'location.live_map_viewed')->where('user_id', $admin->id)->exists())->toBeTrue();
});
