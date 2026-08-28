<?php

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use Spatie\Permission\Models\Permission;

test('a customer can create a question', function () {
    $customer = User::factory()->create(['user_type' => UserType::Customer]);

    expect($customer->can('create', ProductQuestion::class))->toBeTrue();
});

test('the asker can view and delete their own question', function () {
    $customer = User::factory()->create(['user_type' => UserType::Customer]);
    $question = ProductQuestion::factory()->create(['customer_id' => $customer->id]);

    expect($customer->can('view', $question))->toBeTrue()
        ->and($customer->can('delete', $question))->toBeTrue();
});

test('the owning vendor can view and answer a question on their product', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $question = ProductQuestion::factory()->for($product)->create();

    expect($vendor->user->can('view', $question))->toBeTrue()
        ->and($vendor->user->can('answer', $question))->toBeTrue();
});

test('an active store staff member with store.questions.answer can answer the question', function () {
    Permission::findOrCreate('store.questions.answer', 'web');

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $question = ProductQuestion::factory()->for($product)->create();

    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo('store.questions.answer');
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('answer', $question))->toBeTrue();
});

test('checking access to a question on a product whose vendor has been deleted does not crash', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $question = ProductQuestion::factory()->for($product)->create();
    $vendor->delete();

    expect($question->fresh()->product->vendor)->toBeNull()
        ->and($vendor->user->can('answer', $question->fresh()))->toBeFalse();
});

test('an admin with questions.manage can view, delete, and answer any question', function () {
    Permission::findOrCreate('questions.manage', 'web');

    $admin = User::factory()->create();
    $admin->givePermissionTo('questions.manage');
    $question = ProductQuestion::factory()->create();

    expect($admin->can('view', $question))->toBeTrue()
        ->and($admin->can('delete', $question))->toBeTrue()
        ->and($admin->can('answer', $question))->toBeTrue();
});

test('an unrelated customer cannot view, delete, or answer someone elses question', function () {
    $question = ProductQuestion::factory()->create();
    $stranger = User::factory()->create(['user_type' => UserType::Customer]);

    expect($stranger->can('view', $question))->toBeFalse()
        ->and($stranger->can('delete', $question))->toBeFalse()
        ->and($stranger->can('answer', $question))->toBeFalse();
});
