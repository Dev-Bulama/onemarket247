<?php

use App\Models\Vendor;

/**
 * The storefront's customer login and the vendor panel's login are
 * genuinely separate auth systems (different guards, different session
 * cookies) — unlike the mobile app's single Sanctum-token login that
 * already worked for both account types, a web vendor needed to already
 * know the separate /vendor/login URL existed. These just make that
 * existing page discoverable from the customer-facing pages instead of
 * adding a new auth mechanism.
 */
test('the customer login page links to the vendor login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('vendor.login'), false);
});

test('the home page header shows a vendor login link for a guest', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('vendor.login'), false);
});

test('the home page header shows a vendor dashboard link once logged in as a vendor', function () {
    $vendor = Vendor::factory()->create();

    $this->actingAs($vendor->user, 'vendor')
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('filament.vendor.pages.dashboard'), false);
});
