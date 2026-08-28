<?php

use App\Enums\ReviewStatus;
use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use Spatie\Permission\Models\Permission;

test('a customer can create a review', function () {
    $customer = User::factory()->create(['user_type' => UserType::Customer]);

    expect($customer->can('create', ProductReview::class))->toBeTrue();
});

test('a vendor cannot create a review', function () {
    $vendor = Vendor::factory()->create();

    expect($vendor->user->can('create', ProductReview::class))->toBeFalse();
});

test('the reviewer can view and update their own pending review, but not once approved', function () {
    $customer = User::factory()->create(['user_type' => UserType::Customer]);
    $review = ProductReview::factory()->create(['customer_id' => $customer->id]);

    expect($customer->can('view', $review))->toBeTrue()
        ->and($customer->can('update', $review))->toBeTrue();

    $review->update(['status' => ReviewStatus::Approved]);

    expect($customer->can('update', $review->fresh()))->toBeFalse();
});

test('the reviewer can always delete their own review', function () {
    $customer = User::factory()->create(['user_type' => UserType::Customer]);
    $review = ProductReview::factory()->approved()->create(['customer_id' => $customer->id]);

    expect($customer->can('delete', $review))->toBeTrue();
});

test('the owning vendor can view and respond to a review on their product', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $review = ProductReview::factory()->for($product)->approved()->create();

    expect($vendor->user->can('view', $review))->toBeTrue()
        ->and($vendor->user->can('update', $review))->toBeTrue();
});

test('an active store staff member with store.reviews.respond can view and respond to the review', function () {
    Permission::findOrCreate('store.reviews.respond', 'web');

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $review = ProductReview::factory()->for($product)->approved()->create();

    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo('store.reviews.respond');
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('update', $review))->toBeTrue();
});

test('checking access to a review on a product whose vendor has been deleted does not crash', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $review = ProductReview::factory()->for($product)->approved()->create();
    $vendor->delete();

    expect($review->fresh()->product->vendor)->toBeNull()
        ->and($vendor->user->can('update', $review->fresh()))->toBeFalse();
});

test('an admin with reviews.moderate can view, update, and moderate any review', function () {
    Permission::findOrCreate('reviews.moderate', 'web');

    $admin = User::factory()->create();
    $admin->givePermissionTo('reviews.moderate');
    $review = ProductReview::factory()->create();

    expect($admin->can('view', $review))->toBeTrue()
        ->and($admin->can('update', $review))->toBeTrue()
        ->and($admin->can('delete', $review))->toBeTrue()
        ->and($admin->can('moderate', ProductReview::class))->toBeTrue();
});

test('an unrelated customer cannot view, update, or delete someone elses review', function () {
    $review = ProductReview::factory()->approved()->create();
    $stranger = User::factory()->create(['user_type' => UserType::Customer]);

    expect($stranger->can('view', $review))->toBeFalse()
        ->and($stranger->can('update', $review))->toBeFalse()
        ->and($stranger->can('delete', $review))->toBeFalse();
});
