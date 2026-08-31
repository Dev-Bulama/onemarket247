<?php

namespace App\Actions\Vendor;

use App\Enums\VendorApplicationStatus;
use App\Models\User;
use App\Models\VendorApplication;
use App\Notifications\VendorApplicationRejectedNotification;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Notification;
use Throwable;

class RejectVendorApplicationAction
{
    public function handle(VendorApplication $application, string $reason, ?User $reviewer = null): VendorApplication
    {
        $before = $application->only(['status']);

        $application->update([
            'status' => VendorApplicationStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);

        AuditLogger::record('vendor_application.rejected', $application, $before, $application->only(['status', 'rejection_reason']), $reviewer);

        // The rejection itself already persisted above — a mail transport
        // failure here must not turn a successful rejection into a 500.
        try {
            Notification::route('mail', $application->email)
                ->notify(new VendorApplicationRejectedNotification($application));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $application;
    }
}
