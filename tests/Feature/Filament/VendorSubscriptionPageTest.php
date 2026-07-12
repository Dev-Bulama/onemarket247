<?php

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Enums\VendorSubscriptionStatus;
use App\Filament\Vendor\Pages\Subscription;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Models\VendorSubscriptionPlan;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

test('a vendor can switch to a free plan and the previous subscription is cancelled', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $oldPlan = VendorSubscriptionPlan::factory()->create(['price' => 0, 'is_active' => true]);
    $oldSubscription = VendorSubscription::factory()->create([
        'vendor_id' => $vendor->id,
        'vendor_subscription_plan_id' => $oldPlan->id,
        'status' => VendorSubscriptionStatus::Active,
    ]);

    $newPlan = VendorSubscriptionPlan::factory()->create(['price' => 0, 'is_active' => true]);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Subscription::class)
        ->call('switchTo', $newPlan->id);

    $oldSubscription->refresh();
    expect($oldSubscription->status)->toBe(VendorSubscriptionStatus::Cancelled);

    $vendor->refresh();
    expect($vendor->currentSubscription()->vendor_subscription_plan_id)->toBe($newPlan->id);
});

test('a vendor cannot self-service switch to a paid plan', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $paidPlan = VendorSubscriptionPlan::factory()->create(['price' => 2900, 'is_active' => true]);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Subscription::class)
        ->call('switchTo', $paidPlan->id);

    expect(VendorSubscription::where('vendor_id', $vendor->id)->where('vendor_subscription_plan_id', $paidPlan->id)->exists())->toBeFalse();
});

test('vendor staff cannot access the subscription page', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();

    $staffUser = User::factory()->create(['user_type' => UserType::VendorStaff]);
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    test()->actingAs($staffUser, 'vendor')->get('/vendor/subscription')->assertForbidden();
});
