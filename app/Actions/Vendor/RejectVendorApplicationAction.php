<?php

namespace App\Actions\Vendor;

use App\Enums\VendorApplicationStatus;
use App\Models\User;
use App\Models\VendorApplication;
use App\Notifications\VendorApplicationRejectedNotification;
use Illuminate\Support\Facades\Notification;

class RejectVendorApplicationAction
{
    public function handle(VendorApplication $application, string $reason, ?User $reviewer = null): VendorApplication
    {
        $application->update([
            'status' => VendorApplicationStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
        ]);

        Notification::route('mail', $application->email)
            ->notify(new VendorApplicationRejectedNotification($application));

        return $application;
    }
}
