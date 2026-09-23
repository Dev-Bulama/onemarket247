<?php

use App\Enums\StoreStaffStatus;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use Spatie\Permission\Models\Permission;

test('a customer can start a conversation with a vendor', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $customer = User::factory()->create();
    $token = $customer->createToken('t', ['customer:*'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/conversations', ['vendor_id' => $vendor->id, 'message' => 'Hi there!']);

    $response->assertCreated()
        ->assertJsonPath('data.vendor.id', $vendor->id)
        ->assertJsonPath('data.last_message.body', 'Hi there!');

    expect(Conversation::count())->toBe(1);
});

test('a customer can start a conversation directly from a product', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $customer = User::factory()->create();
    $token = $customer->createToken('t', ['customer:*'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/conversations', ['product_id' => $product->id]);

    $response->assertCreated()->assertJsonPath('data.product.id', $product->id);
});

test('a vendor cannot start a new conversation', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $otherVendor = Vendor::factory()->create();
    Store::factory()->for($otherVendor)->create();
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/conversations', ['vendor_id' => $otherVendor->id])
        ->assertForbidden();
});

test('a customer sees only their own conversations in the index', function () {
    $customer = User::factory()->create();
    $mine = Conversation::factory()->create(['user_id' => $customer->id]);
    Conversation::factory()->create();
    $token = $customer->createToken('t', ['customer:*'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/conversations');

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
});

test('a vendor sees only conversations about their own store in the index', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $mine = Conversation::factory()->create(['vendor_id' => $vendor->id]);
    Conversation::factory()->create();
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/conversations');

    $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $mine->id);
});

test('viewing a conversation returns its messages and marks the other sides messages read', function () {
    $customer = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $customer->id]);
    $vendorMessage = ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $conversation->vendor->user->id]);
    $token = $customer->createToken('t', ['customer:*'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/conversations/{$conversation->id}");

    $response->assertOk()->assertJsonCount(1, 'data.messages');
    expect($vendorMessage->fresh()->read_at)->not->toBeNull();
});

test('a stranger cannot view someone elses conversation', function () {
    $conversation = Conversation::factory()->create();
    $stranger = User::factory()->create();
    $token = $stranger->createToken('t', ['customer:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/conversations/{$conversation->id}")
        ->assertForbidden();
});

test('the customer can send a message and the vendor can reply', function () {
    $customer = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $customer->id]);
    $customerToken = $customer->createToken('t', ['customer:*'])->plainTextToken;
    $vendorToken = $conversation->vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$customerToken}")
        ->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => 'Do you ship to Kano?'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'Do you ship to Kano?');

    $this->withHeader('Authorization', "Bearer {$vendorToken}")
        ->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => 'Yes, we do!'])
        ->assertCreated();

    expect($conversation->messages()->count())->toBe(2);
});

test('an active store staff member with store.conversations.manage can reply on the vendors behalf', function () {
    $permission = Permission::findOrCreate('store.conversations.manage', 'vendor');
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $conversation = Conversation::factory()->create(['vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create();
    $staffUser->givePermissionTo($permission);
    StoreStaff::factory()->create(['store_id' => $store->id, 'user_id' => $staffUser->id, 'status' => StoreStaffStatus::Active]);
    $token = $staffUser->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => 'On it!'])
        ->assertCreated();
});

test('nobody can send a message on a closed conversation', function () {
    $conversation = Conversation::factory()->closed()->create();
    $token = $conversation->user->createToken('t', ['customer:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => 'Still there?'])
        ->assertForbidden();
});

test('an empty message body is rejected', function () {
    $conversation = Conversation::factory()->create();
    $token = $conversation->user->createToken('t', ['customer:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/conversations/{$conversation->id}/messages", ['body' => ''])
        ->assertStatus(422);
});
