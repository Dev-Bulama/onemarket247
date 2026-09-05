<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\VendorOrderStatus;
use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorOrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $shipment = $this->relationLoaded('shipments') ? $this->shipments->last() : null;

        return [
            'id' => $this->id,
            'vendor_order_number' => $this->vendor_order_number,
            'store_name' => $this->whenLoaded('vendor', fn () => $this->vendor?->store?->name),
            'status' => $this->status->value,
            'status_label' => $this->status->getLabel(),
            'subtotal' => Money::make($this->subtotal),
            'shipping_amount' => Money::make($this->shipping_amount),
            'total' => Money::make($this->total),
            'items' => $this->whenLoaded('orderItems', fn () => OrderItemResource::collection($this->orderItems)),
            // The step-by-step fulfilment timeline ("Order Tracking") — every
            // status transition UpdateVendorOrderStatusAction/CancelVendorOrderAction
            // records, oldest first, distinct from the shipment carrier's own
            // events below (a vendor order can have a full status history with
            // no shipment yet, e.g. before it's dispatched).
            'status_histories' => $this->whenLoaded('statusHistories', fn () => $this->statusHistories
                ->sortBy('created_at')
                ->values()
                ->map(fn ($history) => [
                    'status' => $history->status,
                    'status_label' => VendorOrderStatus::tryFrom($history->status)?->getLabel() ?? $history->status,
                    'note' => $history->note,
                    'changed_at' => $history->created_at,
                ])),
            'shipment' => $shipment ? [
                'tracking_number' => $shipment->tracking_number,
                'carrier' => $shipment->relationLoaded('carrier') ? $shipment->carrier?->name : null,
                'status' => $shipment->status->value,
                'status_label' => $shipment->status->getLabel(),
                'shipped_at' => $shipment->shipped_at,
                'estimated_delivery_at' => $shipment->estimated_delivery_at,
                'delivered_at' => $shipment->delivered_at,
                'events' => $shipment->relationLoaded('events') ? $shipment->events->map(fn ($event) => [
                    'status' => $event->status,
                    'location' => $event->location,
                    'description' => $event->description,
                    'occurred_at' => $event->occurred_at,
                ])->values() : [],
            ] : null,
        ];
    }
}
