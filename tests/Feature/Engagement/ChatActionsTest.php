<?php

use App\Actions\Chat\MarkConversationReadAction;
use App\Actions\Chat\SendMessageAction;
use App\Actions\Chat\StartConversationAction;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\NewChatMessageNotification;
use Illuminate\Support\Facades\Notification;

test('starting a conversation with a vendor creates exactly one thread', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $customer = User::factory()->create();

    $conversation = app(StartConversationAction::class)->handle($vendor, $customer);

    expect($conversation->vendor_id)->toBe($vendor->id)
        ->and($conversation->user_id)->toBe($customer->id)
        ->and(Conversation::count())->toBe(1);
});

test('starting a conversation twice with the same vendor reuses the same thread', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $customer = User::factory()->create();

    $first = app(StartConversationAction::class)->handle($vendor, $customer);
    $second = app(StartConversationAction::class)->handle($vendor, $customer);

    expect($second->id)->toBe($first->id)
        ->and(Conversation::count())->toBe(1);
});

test('starting a conversation about a product remembers it as the subject and context', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'name' => 'Cool Widget']);
    $customer = User::factory()->create();

    $conversation = app(StartConversationAction::class)->handle($vendor, $customer, $product);

    expect($conversation->product_id)->toBe($product->id)
        ->and($conversation->subject)->toBe('Cool Widget');
});

test('starting a conversation with an opening message sends it and notifies the vendor', function () {
    Notification::fake();
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $customer = User::factory()->create();

    $conversation = app(StartConversationAction::class)->handle($vendor, $customer, null, 'Is this in stock?');

    expect($conversation->messages)->toHaveCount(1)
        ->and($conversation->messages->first()->body)->toBe('Is this in stock?')
        ->and($conversation->messages->first()->sender_id)->toBe($customer->id);
    Notification::assertSentTo($vendor->user, NewChatMessageNotification::class);
});

test('sending a message updates last_message_at and notifies the other party', function () {
    Notification::fake();
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $conversation = Conversation::factory()->create(['vendor_id' => $vendor->id]);

    $message = app(SendMessageAction::class)->handle($conversation, $conversation->user, 'Hello there');

    expect($message->body)->toBe('Hello there')
        ->and($conversation->fresh()->last_message_at)->not->toBeNull();
    Notification::assertSentTo($vendor->user, NewChatMessageNotification::class);
});

test('a reply from the vendor side notifies the customer, not the vendor', function () {
    Notification::fake();
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $conversation = Conversation::factory()->create(['vendor_id' => $vendor->id]);

    app(SendMessageAction::class)->handle($conversation, $vendor->user, 'We do have stock.');

    Notification::assertSentTo($conversation->user, NewChatMessageNotification::class);
    Notification::assertNotSentTo($vendor->user, NewChatMessageNotification::class);
});

test('marking a conversation read only marks messages from the other side', function () {
    $conversation = Conversation::factory()->create();
    $fromCustomer = ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $conversation->user_id]);
    $vendorUser = $conversation->vendor->user;
    $fromVendor = ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $vendorUser->id]);

    app(MarkConversationReadAction::class)->handle($conversation, $conversation->user);

    expect($fromCustomer->fresh()->read_at)->toBeNull()
        ->and($fromVendor->fresh()->read_at)->not->toBeNull();
});
