<?php

use App\Models\AgentApplication;
use App\Models\User;
use Spatie\Permission\Models\Permission;

test('nobody can create an agent application through the policy — only the public form', function () {
    $user = User::factory()->create();

    expect($user->can('create', AgentApplication::class))->toBeFalse();
});

test('an admin with agents.manage can view, update, and delete any application', function () {
    Permission::findOrCreate('agents.manage', 'web');

    $admin = User::factory()->create();
    $admin->givePermissionTo('agents.manage');
    $application = AgentApplication::factory()->create();

    expect($admin->can('view', $application))->toBeTrue()
        ->and($admin->can('update', $application))->toBeTrue()
        ->and($admin->can('delete', $application))->toBeTrue();
});

test('a user without agents.manage cannot view, update, or delete an application', function () {
    $stranger = User::factory()->create();
    $application = AgentApplication::factory()->create();

    expect($stranger->can('view', $application))->toBeFalse()
        ->and($stranger->can('update', $application))->toBeFalse()
        ->and($stranger->can('delete', $application))->toBeFalse();
});
