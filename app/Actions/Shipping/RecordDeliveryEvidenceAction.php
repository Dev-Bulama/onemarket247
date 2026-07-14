<?php

namespace App\Actions\Shipping;

use App\Enums\DeliveryEvidenceType;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryEvidence;
use Illuminate\Http\UploadedFile;

class RecordDeliveryEvidenceAction
{
    public function handle(
        DeliveryAssignment $assignment,
        DeliveryEvidenceType $type,
        UploadedFile $file,
        ?string $recipientName = null,
        ?string $notes = null,
    ): DeliveryEvidence {
        $path = $file->store("delivery-evidence/{$assignment->id}", 'local');

        return $assignment->evidence()->create([
            'type' => $type,
            'file_path' => $path,
            'recipient_name' => $recipientName,
            'notes' => $notes,
        ]);
    }
}
