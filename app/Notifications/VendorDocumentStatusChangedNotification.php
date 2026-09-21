<?php

namespace App\Notifications;

use App\Enums\VendorDocumentStatus;
use App\Models\VendorDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent whenever ReviewVendorDocumentAction approves or rejects a document,
 * whether it's still attached to a VendorApplication (onboarding, no login
 * account yet — delivered by email address via Notification::route) or to
 * an already-approved Vendor (delivered to their User account).
 */
class VendorDocumentStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly VendorDocument $document) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $this->document->vendorApplication?->full_name ?? $this->document->vendor?->business_name ?? 'there';
        $type = $this->document->type->getLabel();

        if ($this->document->status === VendorDocumentStatus::Verified) {
            return (new MailMessage)
                ->subject('Your document has been verified')
                ->greeting('Hello '.$name.',')
                ->line('Your "'.$type.'" document has been reviewed and verified.');
        }

        return (new MailMessage)
            ->subject('Action needed: your document was not approved')
            ->greeting('Hello '.$name.',')
            ->line('Your "'.$type.'" document could not be verified.')
            ->line('Reason: '.$this->document->rejection_reason)
            ->line('Please upload a new document of this type to resolve this.');
    }
}
