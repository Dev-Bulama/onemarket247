<?php

namespace App\Actions\Shipping;

use App\Enums\DeliveryAssignmentStatus;
use App\Models\DeliveryAssignment;

class UpdateDeliveryAssignmentStatusAction
{
    public function handle(DeliveryAssignment $assignment, DeliveryAssignmentStatus $status): DeliveryAssignment
    {
        $assignment->update([
            'status' => $status,
            'delivered_at' => $status === DeliveryAssignmentStatus::Delivered ? now() : $assignment->delivered_at,
        ]);

        return $assignment->fresh();
    }
}
