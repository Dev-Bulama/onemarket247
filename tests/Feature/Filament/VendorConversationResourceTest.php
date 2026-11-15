<?php

use App\Filament\Vendor\Resources\Conversations\Pages\ViewConversation;
use App\Filament\Vendor\Resources\Conversations\RelationManagers\MessagesRelationManager;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    Filament::setCurrentPanel('vendor');
});

test('a vendor owner can load their conversation index and view pages', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $conversation = Conversation::factory()->create(['vendor_id' => $vendor->id]);
    ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $conversation->user_id]);

    $this->actingAs($vendor->user, 'vendor')->get('/vendor/conversations')->assertOk();
    $this->actingAs($vendor->user, 'vendor')->get("/vendor/conversations/{$conversation->id}")->assertOk();
});

test('a customers message to a vendor shows up in that vendors conversation list', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $customer = User::factory()->create(['name' => 'Jane Customer']);
    $conversation = Conversation::factory()->create(['vendor_id' => $vendor->id, 'user_id' => $customer->id]);
    ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $customer->id, 'body' => 'Is this still in stock?']);

    $this->actingAs($vendor->user, 'vendor')
        ->get('/vendor/conversations')
        ->assertOk()
        ->assertSee('Jane Customer');
});

test('a vendor cannot open another vendors conversation', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $otherVendor = Vendor::factory()->create();
    Store::factory()->for($otherVendor)->create();
    $otherConversation = Conversation::factory()->create(['vendor_id' => $otherVendor->id]);

    $this->actingAs($vendor->user, 'vendor')
        ->get("/vendor/conversations/{$otherConversation->id}")->assertNotFound();
});

test('a vendor owner can reply to a customer in their conversation', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $customer = User::factory()->create();
    $conversation = Conversation::factory()->create(['vendor_id' => $vendor->id, 'user_id' => $customer->id]);
    ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'sender_id' => $customer->id]);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(MessagesRelationManager::class, ['ownerRecord' => $conversation, 'pageClass' => ViewConversation::class])
        ->callTableAction('create', data: ['body' => 'Yes, still available!']);

    $message = $conversation->messages()->latest()->first();
    expect($message->body)->toBe('Yes, still available!')
        ->and($message->sender_id)->toBe($vendor->user->id);
});

test('a closed conversations reply action is hidden', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $conversation = Conversation::factory()->closed()->create(['vendor_id' => $vendor->id]);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(MessagesRelationManager::class, ['ownerRecord' => $conversation, 'pageClass' => ViewConversation::class])
        ->assertTableActionHidden('create');
});

test('a vendor owner can close and reopen their conversation', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $conversation = Conversation::factory()->create(['vendor_id' => $vendor->id]);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(ViewConversation::class, ['record' => $conversation->getRouteKey()])
        ->callAction('close');

    expect($conversation->fresh()->isClosed())->toBeTrue();

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(ViewConversation::class, ['record' => $conversation->getRouteKey()])
        ->callAction('reopen');

    expect($conversation->fresh()->isClosed())->toBeFalse();
});
