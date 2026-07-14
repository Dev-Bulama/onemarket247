<?php

namespace App\Actions\Shipping;

use App\Enums\DeliveryAssignmentStatus;
use App\Exceptions\ShipmentAlreadyAssignedException;
use App\Models\DeliveryAssignment;
use App\Models\Shipment;
use App\Models\User;

class AssignDeliveryAction
{
    public function handle(Shipment $shipment, string $assigneeName, ?string $assigneePhone, ?User $actor = null): DeliveryAssignment
    {
        if ($shipment->deliveryAssignment !== null) {
            throw new ShipmentAlreadyAssignedException('This shipment already has a delivery assignment.');
        }

        return $shipment->deliveryAssignment()->create([
            'assignee_name' => $assigneeName,
            'assignee_phone' => $assigneePhone,
            'status' => DeliveryAssignmentStatus::Assigned,
            'assigned_at' => now(),
            'assigned_by' => $actor?->id,
        ]);
    }
}
