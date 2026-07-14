<?php

namespace App\Actions\Shipping;

use App\Actions\Order\UpdateVendorOrderStatusAction;
use App\Enums\ShipmentStatus;
use App\Enums\VendorOrderStatus;
use App\Models\PickupStation;
use App\Models\Shipment;
use App\Models\ShippingCarrier;
use App\Models\User;
use App\Models\VendorOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates the shipment record and advances the vendor order to Shipped in
 * one step — see docs/architecture/07-vendor-dashboard.md's
 * "/vendor/shipments: Assign carrier/tracking number". Reuses
 * UpdateVendorOrderStatusAction's own transition guard (ready_for_pickup ->
 * shipped) rather than duplicating it, so an order that isn't ready for
 * pickup yet is rejected the same way any other illegal transition is.
 */
class CreateShipmentAction
{
    public function __construct(private readonly UpdateVendorOrderStatusAction $updateStatus) {}

    public function handle(
        VendorOrder $vendorOrder,
        ?ShippingCarrier $carrier,
        ?string $trackingNumber,
        ?PickupStation $pickupStation,
        ?Carbon $estimatedDeliveryAt,
        ?User $actor = null,
    ): Shipment {
        return DB::transaction(function () use ($vendorOrder, $carrier, $trackingNumber, $pickupStation, $estimatedDeliveryAt, $actor) {
            $shipment = $vendorOrder->shipments()->create([
                'shipping_carrier_id' => $carrier?->id,
                'pickup_station_id' => $pickupStation?->id,
                'tracking_number' => $trackingNumber,
                'status' => ShipmentStatus::Shipped,
                'shipped_at' => now(),
                'estimated_delivery_at' => $estimatedDeliveryAt,
            ]);

            $shipment->events()->create([
                'status' => ShipmentStatus::Shipped,
                'occurred_at' => now(),
                'created_by' => $actor?->id,
            ]);

            $this->updateStatus->handle(
                $vendorOrder,
                VendorOrderStatus::Shipped,
                $trackingNumber ? "Shipped via {$carrier?->name} (tracking: {$trackingNumber})" : 'Shipped',
                $actor,
            );

            return $shipment->fresh();
        });
    }
}
