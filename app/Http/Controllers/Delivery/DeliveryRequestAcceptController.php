<?php

namespace App\Http\Controllers\Delivery;

use App\Actions\Delivery\AcceptDeliveryRequestAction;
use App\Exceptions\DeliveryRequestAlreadyAcceptedException;
use App\Http\Controllers\Controller;
use App\Models\DeliveryRequestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * The no-login page a delivery partner reaches from their SMS/email
 * alert (see App\Notifications\DeliveryRequestBroadcastNotification) —
 * the unguessable `token` route key is the entire access control, per
 * Priority 7's scope decision to avoid a new login system.
 */
class DeliveryRequestAcceptController extends Controller
{
    public function show(DeliveryRequestNotification $notification): View
    {
        $notification->loadMissing([
            'deliveryRequest.shipment.vendorOrder.order.shippingCity',
            'deliveryRequest.shipment.vendorOrder.order.shippingState',
            'deliveryPartner',
        ]);

        return view('delivery.accept', ['notification' => $notification]);
    }

    public function accept(DeliveryRequestNotification $notification): RedirectResponse
    {
        try {
            $assignment = app(AcceptDeliveryRequestAction::class)->handle($notification);
        } catch (DeliveryRequestAlreadyAcceptedException $exception) {
            return redirect()->route('delivery-requests.accept', $notification->token)->with('error', $exception->getMessage());
        }

        return redirect()->route('deliveries.track', $assignment->tracking_token)
            ->with('status', 'Delivery accepted — thanks! You can track and update your progress from this page.');
    }
}
