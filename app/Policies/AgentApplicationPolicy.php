<?php

namespace App\Policies;

use App\Models\AgentApplication;
use App\Models\User;

/**
 * Applications are created by unauthenticated applicants via the public
 * application form, never through this policy — create() is hard-false.
 * Review is admin-only, gated by agents.manage.
 */
class AgentApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('agents.manage');
    }

    public function view(User $user, AgentApplication $application): bool
    {
        return $user->can('agents.manage');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, AgentApplication $application): bool
    {
        return $user->can('agents.manage');
    }

    /**
     * Deleting an Approved application is safe: ApproveAgentApplicationAction
     * already migrates every agent_document off agent_application_id onto
     * agent_id at approval time, so this only ever removes the historical
     * "how they applied" record — the agent and their documents are
     * untouched.
     */
    public function delete(User $user, AgentApplication $application): bool
    {
        return $user->can('agents.manage');
    }
}
