<?php

namespace App\Actions\Shipping;

use App\Actions\Order\UpdateVendorOrderStatusAction;
use App\Enums\ShipmentStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Appends a tracking event and updates the shipment's own status.
 * out_for_delivery/delivered additionally advance the parent vendor order
 * via UpdateVendorOrderStatusAction (reusing its shipped ->
 * out_for_delivery -> delivered transition guard) — the same
 * "hook into the existing transition action" pattern Phase 14 used for
 * wallet settlement. pending/packed/in_transit/failed/returned have no
 * matching VendorOrderStatus and only update the shipment.
 */
class RecordShipmentEventAction
{
    private const VENDOR_ORDER_STATUS_MAP = [
        'out_for_delivery' => VendorOrderStatus::OutForDelivery,
        'delivered' => VendorOrderStatus::Delivered,
    ];

    public function __construct(private readonly UpdateVendorOrderStatusAction $updateStatus) {}

    public function handle(
        Shipment $shipment,
        ShipmentStatus $status,
        ?string $location = null,
        ?string $description = null,
        ?User $actor = null,
    ): ShipmentEvent {
        return DB::transaction(function () use ($shipment, $status, $location, $description, $actor) {
            $event = $shipment->events()->create([
                'status' => $status,
                'location' => $location,
                'description' => $description,
                'occurred_at' => now(),
                'created_by' => $actor?->id,
            ]);

            $shipment->update([
                'status' => $status,
                'delivered_at' => $status === ShipmentStatus::Delivered ? now() : $shipment->delivered_at,
            ]);

            $vendorOrderTarget = self::VENDOR_ORDER_STATUS_MAP[$status->value] ?? null;

            if ($vendorOrderTarget !== null) {
                $this->updateStatus->handle($shipment->vendorOrder, $vendorOrderTarget, $description, $actor);
            }

            return $event;
        });
    }
}
