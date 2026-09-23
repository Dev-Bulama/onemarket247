<?php

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\Conversation;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use Spatie\Permission\Models\Permission;

test('a customer can create a conversation', function () {
    $customer = User::factory()->create(['user_type' => UserType::Customer]);

    expect($customer->can('create', Conversation::class))->toBeTrue();
});

test('a vendor cannot create a conversation — they only reply to existing ones', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    expect($vendor->user->can('create', Conversation::class))->toBeFalse();
});

test('the other party can view and reply to their own conversation', function () {
    $customer = User::factory()->create(['user_type' => UserType::Customer]);
    $conversation = Conversation::factory()->create(['user_id' => $customer->id]);

    expect($customer->can('view', $conversation))->toBeTrue()
        ->and($customer->can('reply', $conversation))->toBeTrue();
});

test('the owning vendor can view and reply to a conversation about their store', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $conversation = Conversation::factory()->create(['vendor_id' => $vendor->id]);

    expect($vendor->user->can('view', $conversation))->toBeTrue()
        ->and($vendor->user->can('reply', $conversation))->toBeTrue();
});

test('an active store staff member with store.conversations.manage can reply', function () {
    $permission = Permission::findOrCreate('store.conversations.manage', 'vendor');

    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $conversation = Conversation::factory()->create(['vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo($permission);
    StoreStaff::factory()->create([
        'store_id' => $store->id,
        'user_id' => $staffUser->id,
        'status' => StoreStaffStatus::Active,
    ]);

    expect($staffUser->can('reply', $conversation))->toBeTrue();
});

test('an admin with conversations.moderate can view and reply to any conversation', function () {
    Permission::findOrCreate('conversations.moderate', 'web');

    $admin = User::factory()->create();
    $admin->givePermissionTo('conversations.moderate');
    $conversation = Conversation::factory()->create();

    expect($admin->can('view', $conversation))->toBeTrue()
        ->and($admin->can('reply', $conversation))->toBeTrue();
});

test('an unrelated customer cannot view or reply to someone elses conversation', function () {
    $conversation = Conversation::factory()->create();
    $stranger = User::factory()->create(['user_type' => UserType::Customer]);

    expect($stranger->can('view', $conversation))->toBeFalse()
        ->and($stranger->can('reply', $conversation))->toBeFalse();
});

test('nobody can reply to a closed conversation, not even the owning vendor', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $conversation = Conversation::factory()->closed()->create(['vendor_id' => $vendor->id]);

    expect($conversation->isClosed())->toBeTrue()
        ->and($vendor->user->can('reply', $conversation))->toBeFalse();
});
