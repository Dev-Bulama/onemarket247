<?php

namespace App\Actions\Delivery;

use App\Models\DeliveryPartner;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Collection;

/**
 * City-then-state-then-anyone specificity fallback for who gets alerted
 * about a shipment's delivery request — mirrors
 * App\Actions\Shipping\ResolveShippingZoneAction's own most-specific-
 * match-or-fall-through shape. Matches against the parent order's
 * shipping destination, since that's who the partner ultimately needs to
 * reach.
 */
class ResolveEligibleDeliveryPartnersAction
{
    public function handle(Shipment $shipment): Collection
    {
        $order = $shipment->vendorOrder->order;

        if ($order->shipping_city_id !== null) {
            $partners = DeliveryPartner::active()->where('city_id', $order->shipping_city_id)->get();

            if ($partners->isNotEmpty()) {
                return $partners;
            }
        }

        if ($order->shipping_state_id !== null) {
            $partners = DeliveryPartner::active()->where('state_id', $order->shipping_state_id)->get();

            if ($partners->isNotEmpty()) {
                return $partners;
            }
        }

        return DeliveryPartner::active()->get();
    }
}
