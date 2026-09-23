<?php

use App\Enums\AgentApplicationStatus;
use App\Filament\Resources\AgentApplications\Pages\ListAgentApplications;
use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function superAdminForAgentApplications(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load the agent applications list page', function () {
    $admin = superAdminForAgentApplications();

    $this->actingAs($admin, 'admin')->get('/admin/agent-applications')->assertOk();
});

test('an admin without agents.manage cannot access agent applications', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/agent-applications')->assertForbidden();
});

test('an admin can approve a pending application from the list', function () {
    $admin = superAdminForAgentApplications();
    $application = AgentApplication::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListAgentApplications::class)
        ->callTableAction('approve', $application);

    $application->refresh();
    expect($application->status)->toBe(AgentApplicationStatus::Approved)
        ->and($application->agent_id)->not->toBeNull();
});

test('approving an application with a conflicting email shows an error instead of crashing', function () {
    $admin = superAdminForAgentApplications();
    Agent::factory()->create(['email' => 'taken@example.com']);
    $application = AgentApplication::factory()->create(['email' => 'taken@example.com']);

    Livewire::actingAs($admin, 'admin')
        ->test(ListAgentApplications::class)
        ->callTableAction('approve', $application)
        ->assertNotified('Cannot approve: email "taken@example.com" is already used by another agent. Resolve the conflicting record before approving.');

    expect($application->fresh()->status)->toBe(AgentApplicationStatus::Pending);
});

test('an admin can reject a pending application with a reason', function () {
    Notification::fake();

    $admin = superAdminForAgentApplications();
    $application = AgentApplication::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListAgentApplications::class)
        ->callTableAction('reject', $application, data: ['reason' => 'Incomplete identity document']);

    $application->refresh();
    expect($application->status)->toBe(AgentApplicationStatus::Rejected)
        ->and($application->rejection_reason)->toBe('Incomplete identity document');
});

test('the approve and reject actions are hidden once an application is no longer pending', function () {
    $admin = superAdminForAgentApplications();
    $application = AgentApplication::factory()->approved()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListAgentApplications::class)
        ->assertTableActionHidden('approve', $application)
        ->assertTableActionHidden('reject', $application);
});
