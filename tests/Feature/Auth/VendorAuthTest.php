<?php

use App\Models\Vendor;

test('an approved vendor can sign in and reach the dashboard', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['password' => bcrypt('VendorPass123!')]);

    $response = $this->post('/vendor/login', [
        'email' => $vendor->user->email,
        'password' => 'VendorPass123!',
    ]);

    $this->assertAuthenticatedAs($vendor->user, 'vendor');
    $response->assertRedirect(route('vendor.dashboard'));

    $this->get('/vendor/dashboard')->assertSee($vendor->business_name);
});

test('a suspended vendor cannot access the dashboard even with correct credentials', function () {
    $vendor = Vendor::factory()->suspended()->create();
    $vendor->user->update(['password' => bcrypt('VendorPass123!')]);

    $response = $this->post('/vendor/login', [
        'email' => $vendor->user->email,
        'password' => 'VendorPass123!',
    ]);

    $this->assertGuest('vendor');
    $response->assertSessionHasErrors('email');
});

test('a vendor logout clears the vendor guard session', function () {
    $vendor = Vendor::factory()->create();

    $this->actingAs($vendor->user, 'vendor')->post('/vendor/logout');

    $this->assertGuest('vendor');
});
