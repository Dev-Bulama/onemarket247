<?php

namespace App\Actions\Agent;

use App\Enums\AgentApplicationStatus;
use App\Models\AgentApplication;
use App\Models\User;
use App\Notifications\AgentApplicationRejectedNotification;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Notification;
use Throwable;

class RejectAgentApplicationAction
{
    public function handle(AgentApplication $application, string $reason, ?User $reviewer = null): AgentApplication
    {
        $before = $application->only(['status']);

        $application->update([
            'status' => AgentApplicationStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);

        AuditLogger::record('agent_application.rejected', $application, $before, $application->only(['status', 'rejection_reason']), $reviewer);

        try {
            Notification::route('mail', $application->email)
                ->notify(new AgentApplicationRejectedNotification($application));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $application;
    }
}
