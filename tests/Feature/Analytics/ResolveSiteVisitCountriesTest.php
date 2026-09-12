<?php

use App\Models\IpGeolocation;
use App\Models\SiteVisit;
use Illuminate\Support\Facades\Http;

test('it resolves unresolved site visits and caches the lookup per IP', function () {
    Http::fake([
        'ip-api.com/*' => Http::response(['status' => 'success', 'countryCode' => 'NG']),
    ]);

    SiteVisit::factory()->count(2)->create(['ip_address' => '102.89.1.1', 'country_code' => null]);
    SiteVisit::factory()->create(['ip_address' => '41.1.1.1', 'country_code' => null]);

    $this->artisan('analytics:resolve-site-visit-countries')->assertSuccessful();

    expect(SiteVisit::where('ip_address', '102.89.1.1')->pluck('country_code')->unique()->all())->toBe(['NG'])
        ->and(SiteVisit::where('ip_address', '41.1.1.1')->first()->country_code)->toBe('NG')
        ->and(IpGeolocation::count())->toBe(2);

    Http::assertSentCount(2);
});

test('an IP already cached in ip_geolocations is not looked up again', function () {
    Http::fake([
        'ip-api.com/*' => Http::response(['status' => 'success', 'countryCode' => 'GH']),
    ]);

    IpGeolocation::create(['ip_address' => '154.1.1.1', 'country_code' => 'US', 'resolved_at' => now()]);
    SiteVisit::factory()->create(['ip_address' => '154.1.1.1', 'country_code' => null]);

    $this->artisan('analytics:resolve-site-visit-countries');

    expect(SiteVisit::first()->country_code)->toBe('US');
    Http::assertNothingSent();
});

test('a lookup the API cannot resolve is stamped with the XX sentinel and not retried', function () {
    Http::fake([
        'ip-api.com/*' => Http::response(['status' => 'fail']),
    ]);

    SiteVisit::factory()->create(['ip_address' => '10.0.0.5', 'country_code' => null]);

    $this->artisan('analytics:resolve-site-visit-countries');

    expect(SiteVisit::first()->country_code)->toBe('XX');

    $this->artisan('analytics:resolve-site-visit-countries');

    Http::assertSentCount(1);
});

test('already-resolved site visits are left untouched', function () {
    Http::fake();

    SiteVisit::factory()->create(['ip_address' => '10.0.0.9', 'country_code' => 'FR']);

    $this->artisan('analytics:resolve-site-visit-countries');

    expect(SiteVisit::first()->country_code)->toBe('FR');
    Http::assertNothingSent();
});

test('visits with no ip address are left untouched', function () {
    Http::fake();

    SiteVisit::factory()->create(['ip_address' => null, 'country_code' => null]);

    $this->artisan('analytics:resolve-site-visit-countries');

    expect(SiteVisit::first()->country_code)->toBeNull();
    Http::assertNothingSent();
});
