<?php

use App\Models\Conversation;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;

test('the product page shows a chat with vendor button and a call button when the store has a phone', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create(['phone' => '+2348012345678']);
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $customer = User::factory()->create();

    $response = $this->actingAs($customer, 'web')->get(route('products.show', $product));

    $response->assertOk()
        ->assertSee('Chat with Vendor')
        ->assertSee('tel:+2348012345678', false);
});

test('a guest sees a login prompt instead of a chat button', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    $this->get(route('products.show', $product))
        ->assertOk()
        ->assertSee('Chat with Vendor')
        ->assertSee(route('login'), false);
});

test('a customer can start a chat from a product page and land on the thread', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $customer = User::factory()->create();

    $response = $this->actingAs($customer, 'web')
        ->post(route('account.conversations.store'), ['product_id' => $product->id]);

    $conversation = Conversation::firstOrFail();
    $response->assertRedirect(route('account.conversations.show', $conversation));
    expect($conversation->vendor_id)->toBe($vendor->id)
        ->and($conversation->user_id)->toBe($customer->id);
});

test('a customer can reply on the thread page and see the message history', function () {
    $customer = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $customer->id]);

    $this->actingAs($customer, 'web')
        ->post(route('account.conversations.reply', $conversation), ['body' => 'When will it ship?'])
        ->assertRedirect();

    $this->actingAs($customer, 'web')
        ->get(route('account.conversations.show', $conversation))
        ->assertOk()
        ->assertSee('When will it ship?');
});

test('a stranger gets a 403 visiting someone elses conversation thread', function () {
    $conversation = Conversation::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($stranger, 'web')
        ->get(route('account.conversations.show', $conversation))
        ->assertForbidden();
});

test('the account chats list shows the customers own conversations', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $customer = User::factory()->create();
    $conversation = Conversation::factory()->create(['user_id' => $customer->id, 'vendor_id' => $vendor->id]);

    $this->actingAs($customer, 'web')
        ->get(route('account.conversations.index'))
        ->assertOk()
        ->assertSee($store->name);
});
