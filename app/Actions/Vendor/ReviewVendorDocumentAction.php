<?php

namespace App\Actions\Vendor;

use App\Enums\VendorDocumentStatus;
use App\Models\User;
use App\Models\VendorDocument;
use App\Notifications\VendorDocumentStatusChangedNotification;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Admin review of a single uploaded vendor document — either at the
 * onboarding stage (vendor_application_id set, no login account yet) or
 * for an already-approved vendor's later upload (vendor_id set). Both
 * cases share the same VendorDocument model/status, so one action serves
 * both App\Filament\Resources\VendorApplications and
 * App\Filament\Resources\Vendors' Documents relation manager.
 */
class ReviewVendorDocumentAction
{
    public function approve(VendorDocument $document, User $reviewer): void
    {
        $before = $document->only(['status', 'rejection_reason']);

        $document->update([
            'status' => VendorDocumentStatus::Verified,
            'rejection_reason' => null,
            'verified_by' => $reviewer->id,
            'verified_at' => now(),
        ]);

        AuditLogger::record('vendor_document.approved', $document, $before, $document->only(['status', 'rejection_reason']), $reviewer);

        $this->notify($document);
    }

    /**
     * There's no separate "resubmission requested" status (see
     * VendorDocumentStatus's 3 cases) — a rejection with a clear reason
     * already tells the applicant/vendor what to fix, and they resubmit by
     * uploading a new document of the same type through their own upload
     * page, which this same review flow then covers again.
     */
    public function reject(VendorDocument $document, string $reason, User $reviewer): void
    {
        $before = $document->only(['status', 'rejection_reason']);

        $document->update([
            'status' => VendorDocumentStatus::Rejected,
            'rejection_reason' => $reason,
            'verified_by' => $reviewer->id,
            'verified_at' => now(),
        ]);

        AuditLogger::record('vendor_document.rejected', $document, $before, $document->only(['status', 'rejection_reason']), $reviewer);

        $this->notify($document);
    }

    private function notify(VendorDocument $document): void
    {
        try {
            if ($document->vendor_application_id) {
                Notification::route('mail', $document->vendorApplication->email)
                    ->notify(new VendorDocumentStatusChangedNotification($document));

                return;
            }

            $document->vendor?->user?->notify(new VendorDocumentStatusChangedNotification($document));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
