<?php

namespace App\Actions\Delivery;

use App\Actions\Shipping\AssignDeliveryAction;
use App\Enums\DeliveryRequestStatus;
use App\Exceptions\DeliveryRequestAlreadyAcceptedException;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryRequest;
use App\Models\DeliveryRequestNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * First-accept-wins: the row lock on the parent DeliveryRequest is what
 * makes two partners tapping "accept" within milliseconds of each other
 * safe — the second transaction blocks until the first commits, then sees
 * status already Accepted and is rejected before ever creating a
 * DeliveryAssignment.
 */
class AcceptDeliveryRequestAction
{
    public function __construct(private readonly AssignDeliveryAction $assignDelivery) {}

    public function handle(DeliveryRequestNotification $notification): DeliveryAssignment
    {
        return DB::transaction(function () use ($notification) {
            $request = DeliveryRequest::whereKey($notification->delivery_request_id)->lockForUpdate()->firstOrFail();

            if ($request->status !== DeliveryRequestStatus::Pending) {
                throw new DeliveryRequestAlreadyAcceptedException('This delivery has already been accepted by another partner.');
            }

            $partner = $notification->deliveryPartner;

            $assignment = $this->assignDelivery->handle($request->shipment, $partner->full_name, $partner->phone);

            $assignment->update([
                'delivery_partner_id' => $partner->id,
                'delivery_request_id' => $request->id,
                'tracking_token' => Str::random(48),
            ]);

            $request->update([
                'status' => DeliveryRequestStatus::Accepted,
                'accepted_delivery_partner_id' => $partner->id,
                'accepted_at' => now(),
            ]);

            $notification->update(['responded_at' => now()]);

            return $assignment->fresh();
        });
    }
}
