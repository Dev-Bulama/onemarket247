<?php

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Enums\VendorSubscriptionStatus;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Models\VendorSubscriptionPlan;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

test('a vendor can view their plans and current subscription', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $plan = VendorSubscriptionPlan::factory()->create(['price' => 0, 'is_active' => true]);
    VendorSubscription::factory()->create([
        'vendor_id' => $vendor->id,
        'vendor_subscription_plan_id' => $plan->id,
        'status' => VendorSubscriptionStatus::Active,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/subscription')
        ->assertOk()
        ->assertJsonPath('data.current.plan.id', $plan->id);
});

test('a vendor can switch to a free plan and the previous subscription is cancelled', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $oldPlan = VendorSubscriptionPlan::factory()->create(['price' => 0, 'is_active' => true]);
    $oldSubscription = VendorSubscription::factory()->create([
        'vendor_id' => $vendor->id,
        'vendor_subscription_plan_id' => $oldPlan->id,
        'status' => VendorSubscriptionStatus::Active,
    ]);

    $newPlan = VendorSubscriptionPlan::factory()->create(['price' => 0, 'is_active' => true]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/subscription/switch', ['plan_id' => $newPlan->id])
        ->assertOk()
        ->assertJsonPath('data.switched', true);

    $oldSubscription->refresh();
    expect($oldSubscription->status)->toBe(VendorSubscriptionStatus::Cancelled);

    $vendor->refresh();
    expect($vendor->currentSubscription()->vendor_subscription_plan_id)->toBe($newPlan->id);
});

test('a vendor cannot self-service switch to a paid plan, and gets a contact-support response', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $paidPlan = VendorSubscriptionPlan::factory()->create(['price' => 2900, 'is_active' => true]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/subscription/switch', ['plan_id' => $paidPlan->id]);

    $response->assertOk()
        ->assertJsonPath('data.switched', false)
        ->assertJsonPath('data.requires_contact_support', true);

    expect(VendorSubscription::where('vendor_id', $vendor->id)->where('vendor_subscription_plan_id', $paidPlan->id)->exists())->toBeFalse();
});

test('vendor staff cannot access the subscription endpoints', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create(['user_type' => UserType::VendorStaff]);
    StoreStaff::factory()->create(['store_id' => $store->id, 'user_id' => $staffUser->id, 'status' => StoreStaffStatus::Active]);
    $token = $staffUser->createToken('t', ['vendor:*'])->plainTextToken;

    $plan = VendorSubscriptionPlan::factory()->create(['price' => 0, 'is_active' => true]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/vendor/subscription')
        ->assertForbidden();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/subscription/switch', ['plan_id' => $plan->id])
        ->assertForbidden();
});
