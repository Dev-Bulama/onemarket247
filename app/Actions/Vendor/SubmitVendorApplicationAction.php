<?php

namespace App\Actions\Vendor;

use App\Enums\VendorDocumentType;
use App\Models\Setting;
use App\Models\VendorApplication;
use App\Models\VendorDocument;
use App\Notifications\VendorApplicationReceivedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Creates the VendorApplication + its VendorDocument rows from the public
 * registration wizard, then immediately runs approval when
 * vendor.approval_mode is "automatic" (see docs/architecture 07 §2 and the
 * settings seeded in SettingsSeeder) — the exact same provisioning code
 * path an admin's manual "approve" action uses.
 */
class SubmitVendorApplicationAction
{
    public function __construct(private readonly ApproveVendorApplicationAction $approve) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, UploadedFile|null>  $documents  keyed by VendorDocumentType value
     */
    public function handle(array $data, array $documents): VendorApplication
    {
        $autoApproved = false;

        $application = DB::transaction(function () use ($data, $documents, &$autoApproved) {
            $application = VendorApplication::create($data);

            foreach ($documents as $type => $file) {
                if (! $file) {
                    continue;
                }

                $path = $file->store("vendor-documents/{$application->id}", 'local');

                VendorDocument::create([
                    'vendor_application_id' => $application->id,
                    'type' => VendorDocumentType::from($type),
                    'file_path' => $path,
                ]);
            }

            if ($this->isAutoApproved()) {
                $autoApproved = true;
                $this->approve->handle($application->fresh());
            }

            return $application->fresh();
        });

        // Only when NOT auto-approved — an auto-approved application
        // already gets ApproveVendorApplicationAction's own "you're
        // approved" email, so a "we received it" email right after would
        // be redundant. Sent after the transaction commits, and never
        // allowed to turn a successful submission into a 500.
        if (! $autoApproved) {
            try {
                Notification::route('mail', $application->email)
                    ->notify(new VendorApplicationReceivedNotification($application));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $application;
    }

    private function isAutoApproved(): bool
    {
        return Setting::where('key', 'vendor.approval_mode')->first()?->typed_value === 'automatic';
    }
}
