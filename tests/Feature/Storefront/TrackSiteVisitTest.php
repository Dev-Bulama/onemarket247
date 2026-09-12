<?php

use App\Enums\StoreStatus;
use App\Models\SiteVisit;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;

test('visiting the homepage records a site visit and sets a visitor cookie', function () {
    $response = $this->get('/');

    $response->assertOk();
    expect(SiteVisit::count())->toBe(1);

    $visit = SiteVisit::first();
    expect($visit->path)->toBe('/')
        ->and($visit->vendor_id)->toBeNull()
        ->and($visit->user_id)->toBeNull()
        ->and($visit->country_code)->toBeNull();

    $response->assertCookie('visitor_id');
});

test('a repeat visit with an existing visitor cookie reuses the same visitor_id', function () {
    $first = $this->get('/');
    $visitorId = $first->getCookie('visitor_id')->getValue();

    $this->withCookie('visitor_id', $visitorId)->get('/');

    expect(SiteVisit::pluck('visitor_id')->unique()->count())->toBe(1)
        ->and(SiteVisit::count())->toBe(2);
});

test('visiting a store page resolves and records the vendor_id', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create(['status' => StoreStatus::Active]);

    $this->get(route('stores.show', $store->slug))->assertOk();

    expect(SiteVisit::first()->vendor_id)->toBe($vendor->id);
});

test('an authenticated customer visit records the user_id', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')->get('/');

    expect(SiteVisit::first()->user_id)->toBe($user->id);
});

test('a 404 response is not recorded as a site visit', function () {
    $this->get('/this-route-does-not-exist')->assertNotFound();

    expect(SiteVisit::count())->toBe(0);
});

test('admin and vendor panel requests are never tracked as site visits', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin, 'admin')->get('/admin/login');

    expect(SiteVisit::count())->toBe(0);
});

test('api requests are never tracked as site visits', function () {
    $this->getJson('/api/v1/products');

    expect(SiteVisit::count())->toBe(0);
});
