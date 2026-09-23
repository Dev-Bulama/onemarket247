<?php

use App\Enums\AgentStatus;
use App\Filament\Resources\Agents\Pages\CreateAgent;
use App\Filament\Resources\Agents\Pages\ListAgents;
use App\Models\Agent;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function superAdminForAgents(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load the agents list page', function () {
    $admin = superAdminForAgents();

    $this->actingAs($admin, 'admin')->get('/admin/agents')->assertOk();
});

test('an admin without agents.manage cannot access agents', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/agents')->assertForbidden();
});

test('an admin can add an agent directly, without going through the application flow', function () {
    $admin = superAdminForAgents();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateAgent::class)
        ->fillForm([
            'full_name' => 'Direct Hire Agent',
            'email' => 'direct@example.com',
            'status' => AgentStatus::Approved->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Agent::where('email', 'direct@example.com')->exists())->toBeTrue();
});

test('an admin can suspend and reactivate an agent', function () {
    $admin = superAdminForAgents();
    $agent = Agent::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListAgents::class)
        ->callTableAction('suspend', $agent, data: ['reason' => 'Reported for misconduct.']);

    expect($agent->fresh()->status)->toBe(AgentStatus::Suspended)
        ->and($agent->fresh()->suspended_at)->not->toBeNull();

    Livewire::actingAs($admin, 'admin')
        ->test(ListAgents::class)
        ->callTableAction('reactivate', $agent);

    expect($agent->fresh()->status)->toBe(AgentStatus::Approved)
        ->and($agent->fresh()->suspended_at)->toBeNull();
});

test('an admin can deactivate an agent, removing them from the registered-agent dropdown', function () {
    $admin = superAdminForAgents();
    $agent = Agent::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListAgents::class)
        ->callTableAction('deactivate', $agent);

    expect($agent->fresh()->status)->toBe(AgentStatus::Deactivated);
});

test('a suspended agent no longer appears in the public approved-agents list', function () {
    $agent = Agent::factory()->suspended()->create();

    $this->getJson('/api/v1/agents')->assertJsonMissing(['id' => $agent->id]);
});
