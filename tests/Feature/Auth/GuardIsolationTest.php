<?php

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;

test('a vendor cannot authenticate through the admin guard', function () {
    $vendor = User::factory()->vendorOwner()->create(['password' => bcrypt('secret123')]);

    expect(Auth::guard('admin')->attempt(['email' => $vendor->email, 'password' => 'secret123']))->toBeFalse()
        ->and(Auth::guard('vendor')->attempt(['email' => $vendor->email, 'password' => 'secret123']))->toBeTrue();
});

test('a customer cannot authenticate through the vendor or admin guard', function () {
    $customer = User::factory()->create(['password' => bcrypt('secret123')]);

    expect(Auth::guard('admin')->attempt(['email' => $customer->email, 'password' => 'secret123']))->toBeFalse()
        ->and(Auth::guard('vendor')->attempt(['email' => $customer->email, 'password' => 'secret123']))->toBeFalse()
        ->and(Auth::guard('web')->attempt(['email' => $customer->email, 'password' => 'secret123']))->toBeTrue();
});

test('an admin cannot authenticate through the vendor or web guard', function () {
    $admin = User::factory()->admin()->create(['password' => bcrypt('secret123')]);

    expect(Auth::guard('vendor')->attempt(['email' => $admin->email, 'password' => 'secret123']))->toBeFalse()
        ->and(Auth::guard('web')->attempt(['email' => $admin->email, 'password' => 'secret123']))->toBeFalse()
        ->and(Auth::guard('admin')->attempt(['email' => $admin->email, 'password' => 'secret123']))->toBeTrue();
});

test('the admin Filament panel is scoped to the admin guard', function () {
    expect(Filament::getPanel('admin')->getAuthGuard())->toBe('admin');
});
