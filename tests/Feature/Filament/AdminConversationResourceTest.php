<?php

use App\Filament\Resources\Conversations\Pages\ListConversations;
use App\Filament\Resources\Conversations\Pages\ViewConversation;
use App\Filament\Resources\Conversations\RelationManagers\MessagesRelationManager;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function conversationAdminUser(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load the conversations list page', function () {
    $admin = conversationAdminUser();

    $this->actingAs($admin, 'admin')->get('/admin/conversations')->assertOk();
});

test('an admin without conversations.moderate cannot access conversations', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/conversations')->assertForbidden();
});

test('an admin can view a conversation page', function () {
    $admin = conversationAdminUser();
    $conversation = Conversation::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get("/admin/conversations/{$conversation->id}")
        ->assertOk();
});

test('an admin can see the message history in a conversation', function () {
    $admin = conversationAdminUser();
    $conversation = Conversation::factory()->create();
    $message = ChatMessage::factory()->create(['conversation_id' => $conversation->id, 'body' => 'Hello from customer']);

    Livewire::actingAs($admin, 'admin')
        ->test(MessagesRelationManager::class, ['ownerRecord' => $conversation, 'pageClass' => ViewConversation::class])
        ->assertSee($message->body);
});

test('an admin can send a message into a conversation as themselves', function () {
    $admin = conversationAdminUser();
    $conversation = Conversation::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(MessagesRelationManager::class, ['ownerRecord' => $conversation, 'pageClass' => ViewConversation::class])
        ->callTableAction('create', data: ['body' => 'This is admin support speaking.']);

    $message = $conversation->messages()->latest()->first();
    expect($message->body)->toBe('This is admin support speaking.')
        ->and($message->sender_id)->toBe($admin->id);
});

test('an admin can remove a message for moderation', function () {
    $admin = conversationAdminUser();
    $conversation = Conversation::factory()->create();
    $message = ChatMessage::factory()->create(['conversation_id' => $conversation->id]);

    Livewire::actingAs($admin, 'admin')
        ->test(MessagesRelationManager::class, ['ownerRecord' => $conversation, 'pageClass' => ViewConversation::class])
        ->callTableAction('delete', $message);

    expect(ChatMessage::find($message->id))->toBeNull();
});

test('an admin can close and reopen a conversation', function () {
    $admin = conversationAdminUser();
    $conversation = Conversation::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ViewConversation::class, ['record' => $conversation->getRouteKey()])
        ->callAction('close');

    expect($conversation->fresh()->isClosed())->toBeTrue();

    Livewire::actingAs($admin, 'admin')
        ->test(ViewConversation::class, ['record' => $conversation->getRouteKey()])
        ->callAction('reopen');

    expect($conversation->fresh()->isClosed())->toBeFalse();
});

test('a closed conversation cannot be closed again from the list', function () {
    $admin = conversationAdminUser();
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $conversation = Conversation::factory()->closed()->create(['vendor_id' => $vendor->id]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListConversations::class)
        ->assertCanSeeTableRecords([$conversation]);
});
