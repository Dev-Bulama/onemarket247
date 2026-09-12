<?php

use App\Models\Country;
use App\Models\SiteVisit;
use App\Models\Vendor;
use App\Support\Analytics\VisitorAnalytics;
use Illuminate\Support\Carbon;

test('summary counts total visits and distinct visitors', function () {
    SiteVisit::factory()->create(['visitor_id' => 'a']);
    SiteVisit::factory()->create(['visitor_id' => 'a']);
    SiteVisit::factory()->create(['visitor_id' => 'b']);

    $summary = app(VisitorAnalytics::class)->summary(null, now()->subDay(), now()->addDay());

    expect($summary['visits'])->toBe(3)
        ->and($summary['unique_visitors'])->toBe(2);
});

test('vendor-scoped summary only counts visits to that vendor\'s store', function () {
    $vendor = Vendor::factory()->create();
    $otherVendor = Vendor::factory()->create();

    SiteVisit::factory()->create(['vendor_id' => $vendor->id]);
    SiteVisit::factory()->create(['vendor_id' => $otherVendor->id]);
    SiteVisit::factory()->create(['vendor_id' => null]);

    $summary = app(VisitorAnalytics::class)->summary($vendor->id, now()->subDay(), now()->addDay());

    expect($summary['visits'])->toBe(1);
});

test('daily visits fills every day in the range including zero-visit days', function () {
    $from = Carbon::parse('2026-01-01');
    $to = Carbon::parse('2026-01-03');

    SiteVisit::factory()->create(['created_at' => Carbon::parse('2026-01-02 09:00:00')]);

    $daily = app(VisitorAnalytics::class)->dailyVisits(null, $from, $to);

    expect($daily->all())->toBe([
        '2026-01-01' => 0,
        '2026-01-02' => 1,
        '2026-01-03' => 0,
    ]);
});

test('top countries excludes unresolved and unresolvable visits and ranks by visit count', function () {
    Country::factory()->create(['iso2' => 'NG', 'name' => 'Nigeria']);
    Country::factory()->create(['iso2' => 'US', 'name' => 'United States']);

    SiteVisit::factory()->count(3)->create(['country_code' => 'NG']);
    SiteVisit::factory()->count(1)->create(['country_code' => 'US']);
    SiteVisit::factory()->create(['country_code' => null]);
    SiteVisit::factory()->create(['country_code' => 'XX']);

    $top = app(VisitorAnalytics::class)->topCountries(null, now()->subDay(), now()->addDay());

    expect($top->all())->toBe([
        ['country_code' => 'NG', 'country_name' => 'Nigeria', 'visits' => 3],
        ['country_code' => 'US', 'country_name' => 'United States', 'visits' => 1],
    ]);
});
