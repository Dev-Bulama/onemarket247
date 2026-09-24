<?php

namespace App\Actions\Delivery;

use App\Exceptions\ShipmentAlreadyAssignedException;
use App\Models\DeliveryRequest;
use App\Models\Shipment;
use App\Models\User;
use App\Notifications\DeliveryRequestBroadcastNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

/**
 * Broadcasts a shipment's delivery to every eligible partner (see
 * ResolveEligibleDeliveryPartnersAction) with each getting their own
 * unguessable accept link — the first to accept wins (see
 * AcceptDeliveryRequestAction). A shipment already assigned the old
 * (manual name/phone) way, or already broadcast, can't be broadcast
 * again.
 */
class CreateDeliveryRequestAction
{
    public function __construct(private readonly ResolveEligibleDeliveryPartnersAction $resolvePartners) {}

    public function handle(
        Shipment $shipment,
        int $deliveryFee,
        ?Carbon $requiredBy = null,
        ?string $specialInstructions = null,
        ?User $actor = null,
    ): DeliveryRequest {
        if ($shipment->deliveryAssignment()->exists() || $shipment->deliveryRequest()->exists()) {
            throw new ShipmentAlreadyAssignedException('This shipment already has a delivery assignment or request.');
        }

        $request = DB::transaction(function () use ($shipment, $deliveryFee, $requiredBy, $specialInstructions, $actor) {
            $request = DeliveryRequest::create([
                'shipment_id' => $shipment->id,
                'delivery_fee' => $deliveryFee,
                'required_by' => $requiredBy,
                'special_instructions' => $specialInstructions,
                'created_by' => $actor?->id,
            ]);

            foreach ($this->resolvePartners->handle($shipment) as $partner) {
                $request->notifications()->create([
                    'delivery_partner_id' => $partner->id,
                    'token' => Str::random(48),
                ]);
            }

            return $request->fresh('notifications.deliveryPartner');
        });

        foreach ($request->notifications as $notification) {
            try {
                Notification::route('mail', $notification->deliveryPartner->email)
                    ->notify(new DeliveryRequestBroadcastNotification($notification));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return $request;
    }
}
