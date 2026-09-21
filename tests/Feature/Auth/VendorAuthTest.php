<?php

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Enums\VendorStatus;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;

test('an approved vendor can sign in and reach the dashboard', function () {
    $vendor = Vendor::factory()->create();
    $vendor->user->update(['password' => bcrypt('VendorPass123!')]);

    $response = $this->post('/vendor/login', [
        'email' => $vendor->user->email,
        'password' => 'VendorPass123!',
    ]);

    $this->assertAuthenticatedAs($vendor->user, 'vendor');
    $response->assertRedirect(route('filament.vendor.pages.dashboard'));

    $this->get('/vendor')->assertOk();
});

test('a suspended vendor cannot access the dashboard even with correct credentials', function () {
    $vendor = Vendor::factory()->suspended()->create();
    $vendor->user->update(['password' => bcrypt('VendorPass123!')]);

    $response = $this->post('/vendor/login', [
        'email' => $vendor->user->email,
        'password' => 'VendorPass123!',
    ]);

    $this->assertGuest('vendor');
    $response->assertSessionHasErrors(['email' => 'Your vendor account has been suspended. Contact support for assistance.']);
});

test('a pending vendor sees a status-specific message instead of a generic denial', function () {
    $vendor = Vendor::factory()->pending()->create();
    $vendor->user->update(['password' => bcrypt('VendorPass123!')]);

    $response = $this->post('/vendor/login', [
        'email' => $vendor->user->email,
        'password' => 'VendorPass123!',
    ]);

    $this->assertGuest('vendor');
    $response->assertSessionHasErrors(['email' => "Your vendor application is still under review. We'll email you once a decision has been made."]);
});

test('a rejected vendor sees a status-specific message', function () {
    $vendor = Vendor::factory()->create(['status' => VendorStatus::Rejected]);
    $vendor->user->update(['password' => bcrypt('VendorPass123!')]);

    $response = $this->post('/vendor/login', [
        'email' => $vendor->user->email,
        'password' => 'VendorPass123!',
    ]);

    $response->assertSessionHasErrors(['email' => 'Your vendor application was not approved. Contact support for more information.']);
});

test('a deactivated vendor sees a status-specific message', function () {
    $vendor = Vendor::factory()->create(['status' => VendorStatus::Deactivated]);
    $vendor->user->update(['password' => bcrypt('VendorPass123!')]);

    $response = $this->post('/vendor/login', [
        'email' => $vendor->user->email,
        'password' => 'VendorPass123!',
    ]);

    $response->assertSessionHasErrors(['email' => 'Your vendor account has been deactivated. Contact support to reactivate it.']);
});

test('a suspended store staff member sees a staff-specific message even if the vendor itself is approved', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $staff = User::factory()->create(['user_type' => UserType::VendorStaff, 'password' => bcrypt('VendorPass123!')]);
    StoreStaff::factory()->create(['store_id' => $store->id, 'user_id' => $staff->id, 'status' => StoreStaffStatus::Suspended]);

    $response = $this->post('/vendor/login', [
        'email' => $staff->email,
        'password' => 'VendorPass123!',
    ]);

    $response->assertSessionHasErrors(['email' => 'Your staff access has been suspended. Contact your store owner for assistance.']);
});

test('a vendor logout clears the vendor guard session', function () {
    $vendor = Vendor::factory()->create();

    $this->actingAs($vendor->user, 'vendor')->post('/vendor/logout');

    $this->assertGuest('vendor');
});
