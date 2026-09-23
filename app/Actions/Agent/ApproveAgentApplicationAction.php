<?php

namespace App\Actions\Agent;

use App\Enums\AgentApplicationStatus;
use App\Enums\AgentStatus;
use App\Exceptions\AgentApplicationConflictException;
use App\Models\Agent;
use App\Models\AgentApplication;
use App\Models\AgentDocument;
use App\Models\User;
use App\Notifications\AgentApplicationApprovedNotification;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Provisions the Agent roster row an application only implies until now —
 * used identically by an admin's explicit "approve" action (there is no
 * automatic-approval path for agents, see SubmitAgentApplicationAction).
 */
class ApproveAgentApplicationAction
{
    public function handle(AgentApplication $application, ?User $reviewer = null): Agent
    {
        $this->assertNoConflict($application);

        $agent = DB::transaction(function () use ($application, $reviewer) {
            $agent = Agent::create([
                'full_name' => $application->full_name,
                'email' => $application->email,
                'phone' => $application->phone,
                'country_id' => $application->country_id,
                'state_id' => $application->state_id,
                'city_id' => $application->city_id,
                'postal_code' => $application->postal_code,
                'address' => $application->address,
                'identity_type' => $application->identity_type,
                'identity_number' => $application->identity_number,
                'status' => AgentStatus::Approved,
                'approved_at' => now(),
            ]);

            // agent_application_id is cleared as documents migrate to the new
            // agent — exactly one of agent_id/agent_application_id is meant
            // to be set at a time, same rationale as vendor_documents.
            AgentDocument::where('agent_application_id', $application->id)->update([
                'agent_id' => $agent->id,
                'agent_application_id' => null,
            ]);

            $application->update([
                'status' => AgentApplicationStatus::Approved,
                'agent_id' => $agent->id,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
            ]);

            AuditLogger::record('agent_application.approved', $application, ['status' => 'pending'], ['status' => 'approved', 'agent_id' => $agent->id], $reviewer);

            return $agent;
        });

        // Sent after the transaction commits — a mail transport failure
        // must never turn a successful approval into a 500.
        try {
            Notification::route('mail', $agent->email)
                ->notify(new AgentApplicationApprovedNotification($agent));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $agent;
    }

    /**
     * Time passes between submission and an admin clicking "Approve" — a
     * conflicting agent record can appear in the meantime. Checking here
     * turns that into an actionable admin-facing message instead of an
     * uncaught SQLSTATE[23000] unique-constraint crash.
     */
    private function assertNoConflict(AgentApplication $application): void
    {
        if (Agent::where('email', $application->email)->exists()) {
            throw new AgentApplicationConflictException(
                "Cannot approve: email \"{$application->email}\" is already used by another agent. Resolve the conflicting record before approving."
            );
        }
    }
}
