<?php

use App\Actions\Delivery\CreateDeliveryRequestAction;
use App\Actions\Shipping\CreateShipmentAction;
use App\Enums\DeliveryAssignmentStatus;
use App\Enums\VendorOrderStatus;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\VendorOrder;

function shipmentReadyForDelivery(): Shipment
{
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::ReadyForPickup]);

    return app(CreateShipmentAction::class)->handle($vendorOrder, null, null, null, null);
}

test('a partner can view the accept page for a pending delivery request', function () {
    $shipment = shipmentReadyForDelivery();
    DeliveryPartner::factory()->create();
    $request = app(CreateDeliveryRequestAction::class)->handle($shipment, 1500);
    $notification = $request->notifications->first();

    $response = $this->get(route('delivery-requests.accept', $notification->token));

    $response->assertOk()->assertSee('Accept this delivery');
});

test('accepting a delivery request redirects to the tracking page', function () {
    $shipment = shipmentReadyForDelivery();
    DeliveryPartner::factory()->create();
    $request = app(CreateDeliveryRequestAction::class)->handle($shipment, 1500);
    $notification = $request->notifications->first();

    $response = $this->post(route('delivery-requests.accept.store', $notification->token));

    $assignment = $shipment->fresh()->deliveryAssignment;
    $response->assertRedirect(route('deliveries.track', $assignment->tracking_token));
});

test('a second partner trying to accept an already-accepted request is turned away', function () {
    $shipment = shipmentReadyForDelivery();
    DeliveryPartner::factory()->count(2)->create();
    $request = app(CreateDeliveryRequestAction::class)->handle($shipment, 1500);
    [$first, $second] = $request->notifications;

    $this->post(route('delivery-requests.accept.store', $first->token));
    $response = $this->post(route('delivery-requests.accept.store', $second->token));

    $response->assertRedirect(route('delivery-requests.accept', $second->token));
    $response->assertSessionHas('error');
});

test('a partner can view their tracking page and advance the delivery status', function () {
    $shipment = shipmentReadyForDelivery();
    DeliveryPartner::factory()->create();
    $request = app(CreateDeliveryRequestAction::class)->handle($shipment, 1500);
    $notification = $request->notifications->first();
    $this->post(route('delivery-requests.accept.store', $notification->token));
    $assignment = $shipment->fresh()->deliveryAssignment;

    $show = $this->get(route('deliveries.track', $assignment->tracking_token));
    $show->assertOk()->assertSee('Mark as Picked up');

    $advance = $this->post(route('deliveries.track.advance', $assignment->tracking_token), ['status' => 'picked_up']);

    $advance->assertRedirect(route('deliveries.track', $assignment->tracking_token));
    expect($assignment->fresh()->status)->toBe(DeliveryAssignmentStatus::PickedUp);
});

test('an invalid status transition on the tracking page is rejected', function () {
    $shipment = shipmentReadyForDelivery();
    DeliveryPartner::factory()->create();
    $request = app(CreateDeliveryRequestAction::class)->handle($shipment, 1500);
    $notification = $request->notifications->first();
    $this->post(route('delivery-requests.accept.store', $notification->token));
    $assignment = $shipment->fresh()->deliveryAssignment;

    $response = $this->post(route('deliveries.track.advance', $assignment->tracking_token), ['status' => 'delivered']);

    $response->assertSessionHasErrors('status');
    expect($assignment->fresh()->status)->toBe(DeliveryAssignmentStatus::Assigned);
});

test('an unknown token 404s on both public pages', function () {
    $this->get(route('delivery-requests.accept', 'nonexistent'))->assertNotFound();
    $this->get(route('deliveries.track', 'nonexistent'))->assertNotFound();
});
