<?php

namespace App\Actions\Delivery;

use App\Actions\Shipping\RecordShipmentEventAction;
use App\Actions\Shipping\UpdateDeliveryAssignmentStatusAction;
use App\Enums\DeliveryAssignmentStatus;
use App\Enums\ShipmentStatus;
use App\Exceptions\InvalidDeliveryAssignmentTransitionException;
use App\Models\DeliveryAssignment;
use Illuminate\Support\Facades\DB;

/**
 * The partner-facing progress update (see
 * App\Http\Controllers\Delivery\DeliveryTrackingController) — unlike the
 * bare App\Actions\Shipping\UpdateDeliveryAssignmentStatusAction this
 * wraps, it enforces the assigned -> picked_up -> in_transit -> delivered
 * sequence (a courier can't skip straight to "delivered") and, per
 * Priority 7's requirement that "the system should update the order
 * status automatically as the delivery progresses," also appends the
 * matching App\Models\ShipmentEvent so the shipment/vendor order advance
 * in step. picked_up has no direct ShipmentStatus equivalent of its own,
 * so it's folded into in_transit (the package is now moving); the vendor
 * order itself only ever reaches OutForDelivery/Delivered here, both
 * transitions UpdateVendorOrderStatusAction already allows from Shipped.
 */
class AdvanceDeliveryAssignmentAction
{
    private const ALLOWED_TRANSITIONS = [
        'assigned' => [DeliveryAssignmentStatus::PickedUp, DeliveryAssignmentStatus::Failed],
        'picked_up' => [DeliveryAssignmentStatus::InTransit, DeliveryAssignmentStatus::Failed],
        'in_transit' => [DeliveryAssignmentStatus::Delivered, DeliveryAssignmentStatus::Failed],
    ];

    private const SHIPMENT_STATUS_MAP = [
        'picked_up' => ShipmentStatus::InTransit,
        'in_transit' => ShipmentStatus::OutForDelivery,
        'delivered' => ShipmentStatus::Delivered,
        'failed' => ShipmentStatus::Failed,
    ];

    public function __construct(
        private readonly UpdateDeliveryAssignmentStatusAction $updateAssignment,
        private readonly RecordShipmentEventAction $recordShipmentEvent,
    ) {}

    /**
     * @return array<int, DeliveryAssignmentStatus>
     */
    public static function nextStatusesFor(DeliveryAssignmentStatus $current): array
    {
        return self::ALLOWED_TRANSITIONS[$current->value] ?? [];
    }

    public function handle(DeliveryAssignment $assignment, DeliveryAssignmentStatus $newStatus): DeliveryAssignment
    {
        $allowed = self::ALLOWED_TRANSITIONS[$assignment->status->value] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidDeliveryAssignmentTransitionException(
                "Cannot move a delivery from \"{$assignment->status->getLabel()}\" to \"{$newStatus->getLabel()}\"."
            );
        }

        return DB::transaction(function () use ($assignment, $newStatus) {
            $updated = $this->updateAssignment->handle($assignment, $newStatus);

            $shipmentTarget = self::SHIPMENT_STATUS_MAP[$newStatus->value] ?? null;

            if ($shipmentTarget !== null) {
                $this->recordShipmentEvent->handle($assignment->shipment, $shipmentTarget, description: 'Updated by delivery partner');
            }

            return $updated;
        });
    }
}
