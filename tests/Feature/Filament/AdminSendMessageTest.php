<?php

use App\Enums\AdminMessageAudience;
use App\Enums\UserType;
use App\Filament\Pages\SendAdminMessage;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\AdminBroadcastNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function sendMessageAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can broadcast a message to all customers', function () {
    Notification::fake();
    $admin = sendMessageAdmin();
    $customer = User::factory()->create(['user_type' => UserType::Customer]);
    $vendor = Vendor::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(SendAdminMessage::class)
        ->fillForm([
            'audience' => AdminMessageAudience::AllCustomers->value,
            'subject' => 'Scheduled maintenance',
            'body' => "We'll be down for an hour tonight.",
        ])
        ->call('send');

    Notification::assertSentTo($customer, AdminBroadcastNotification::class);
    Notification::assertNotSentTo($vendor->user, AdminBroadcastNotification::class);
});

test('an admin can broadcast a message to specific people', function () {
    Notification::fake();
    $admin = sendMessageAdmin();
    $chosen = User::factory()->create(['user_type' => UserType::Customer]);
    $notChosen = User::factory()->create(['user_type' => UserType::Customer]);

    Livewire::actingAs($admin, 'admin')
        ->test(SendAdminMessage::class)
        ->fillForm([
            'audience' => AdminMessageAudience::Specific->value,
            'user_ids' => [$chosen->id],
            'subject' => 'Just for you',
            'body' => 'Hello!',
        ])
        ->call('send');

    Notification::assertSentTo($chosen, AdminBroadcastNotification::class);
    Notification::assertNotSentTo($notChosen, AdminBroadcastNotification::class);
});

test('sending a broadcast message stores it in the recipient\'s notifications', function () {
    $admin = sendMessageAdmin();
    $customer = User::factory()->create(['user_type' => UserType::Customer]);

    Livewire::actingAs($admin, 'admin')
        ->test(SendAdminMessage::class)
        ->fillForm([
            'audience' => AdminMessageAudience::Specific->value,
            'user_ids' => [$customer->id],
            'subject' => 'Welcome!',
            'body' => 'Thanks for joining OneMarket247.',
        ])
        ->call('send');

    expect($customer->notifications()->count())->toBe(1)
        ->and($customer->notifications()->first()->data['subject'])->toBe('Welcome!');
});

test('a broadcast message reaches the recipient\'s account notifications page', function () {
    $customer = User::factory()->create(['user_type' => UserType::Customer]);
    $customer->notify(new AdminBroadcastNotification('Welcome!', 'Thanks for joining OneMarket247.', 'The OneMarket247 Team'));

    $this->actingAs($customer)
        ->get(route('account.notifications.index'))
        ->assertOk()
        ->assertSee('Welcome!')
        ->assertSee('Thanks for joining OneMarket247.');
});

test('an admin without notifications.manage cannot access the send message page', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/send-admin-message')->assertForbidden();
});
