<?php

use App\Actions\Delivery\AcceptDeliveryRequestAction;
use App\Actions\Delivery\AdvanceDeliveryAssignmentAction;
use App\Actions\Delivery\CreateDeliveryRequestAction;
use App\Actions\Delivery\ResolveEligibleDeliveryPartnersAction;
use App\Actions\Shipping\AssignDeliveryAction;
use App\Actions\Shipping\CreateShipmentAction;
use App\Enums\DeliveryAssignmentStatus;
use App\Enums\DeliveryRequestStatus;
use App\Enums\ShipmentStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\DeliveryRequestAlreadyAcceptedException;
use App\Exceptions\InvalidDeliveryAssignmentTransitionException;
use App\Exceptions\ShipmentAlreadyAssignedException;
use App\Models\City;
use App\Models\DeliveryPartner;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\State;
use App\Models\VendorOrder;

function readyShipmentFor(?City $city = null, ?State $state = null): Shipment
{
    $order = Order::factory()->create(['shipping_city_id' => $city?->id, 'shipping_state_id' => $state?->id]);
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::ReadyForPickup, 'shipping_amount' => 1500]);

    return app(CreateShipmentAction::class)->handle($vendorOrder, null, null, null, null);
}

test('eligible partners are matched by city first', function () {
    $city = City::factory()->create();
    $otherCity = City::factory()->create();
    $matching = DeliveryPartner::factory()->create(['city_id' => $city->id]);
    DeliveryPartner::factory()->create(['city_id' => $otherCity->id]);

    $shipment = readyShipmentFor($city);

    $partners = app(ResolveEligibleDeliveryPartnersAction::class)->handle($shipment);

    expect($partners->pluck('id')->all())->toBe([$matching->id]);
});

test('eligible partners fall back to the state when no partner matches the city', function () {
    $state = State::factory()->create();
    $matching = DeliveryPartner::factory()->create(['state_id' => $state->id]);
    DeliveryPartner::factory()->create();

    $shipment = readyShipmentFor(city: City::factory()->create(['state_id' => $state->id]), state: $state);

    $partners = app(ResolveEligibleDeliveryPartnersAction::class)->handle($shipment);

    expect($partners->pluck('id')->all())->toBe([$matching->id]);
});

test('eligible partners fall back to every active partner when nothing matches city or state', function () {
    $shipment = readyShipmentFor(null);
    $partnerA = DeliveryPartner::factory()->create();
    $partnerB = DeliveryPartner::factory()->create();
    DeliveryPartner::factory()->suspended()->create();

    $partners = app(ResolveEligibleDeliveryPartnersAction::class)->handle($shipment);

    expect($partners->pluck('id')->sort()->values()->all())->toBe(collect([$partnerA->id, $partnerB->id])->sort()->values()->all());
});

test('creating a delivery request notifies every eligible partner with its own token', function () {
    $shipment = readyShipmentFor(null);
    DeliveryPartner::factory()->count(2)->create();

    $request = app(CreateDeliveryRequestAction::class)->handle($shipment, 1500);

    expect($request->status)->toBe(DeliveryRequestStatus::Pending)
        ->and($request->delivery_fee)->toBe(1500)
        ->and($request->notifications)->toHaveCount(2);

    expect($request->notifications->pluck('token')->unique())->toHaveCount(2);
});

test('a shipment that already has a delivery assignment cannot get a delivery request', function () {
    $shipment = readyShipmentFor(null);
    app(AssignDeliveryAction::class)->handle($shipment, 'Manual Rider', null);

    expect(fn () => app(CreateDeliveryRequestAction::class)->handle($shipment, 1500))
        ->toThrow(ShipmentAlreadyAssignedException::class);
});

test('the first partner to accept gets the assignment and the rest are locked out', function () {
    $shipment = readyShipmentFor(null);
    DeliveryPartner::factory()->count(2)->create();
    $request = app(CreateDeliveryRequestAction::class)->handle($shipment, 1500);
    [$first, $second] = $request->notifications;

    $assignment = app(AcceptDeliveryRequestAction::class)->handle($first);

    expect($assignment->status)->toBe(DeliveryAssignmentStatus::Assigned)
        ->and($assignment->delivery_partner_id)->toBe($first->delivery_partner_id)
        ->and($assignment->tracking_token)->not->toBeNull();

    expect($request->fresh()->status)->toBe(DeliveryRequestStatus::Accepted);

    expect(fn () => app(AcceptDeliveryRequestAction::class)->handle($second))
        ->toThrow(DeliveryRequestAlreadyAcceptedException::class);
});

test('a delivery partner progresses pickup to in-transit to delivered, advancing the vendor order in step', function () {
    $shipment = readyShipmentFor(null);
    DeliveryPartner::factory()->create();
    $request = app(CreateDeliveryRequestAction::class)->handle($shipment, 1500);
    $assignment = app(AcceptDeliveryRequestAction::class)->handle($request->notifications->first());
    $vendorOrder = $shipment->vendorOrder;

    $assignment = app(AdvanceDeliveryAssignmentAction::class)->handle($assignment, DeliveryAssignmentStatus::PickedUp);
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::InTransit);
    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Shipped);

    $assignment = app(AdvanceDeliveryAssignmentAction::class)->handle($assignment, DeliveryAssignmentStatus::InTransit);
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::OutForDelivery);
    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::OutForDelivery);

    $assignment = app(AdvanceDeliveryAssignmentAction::class)->handle($assignment, DeliveryAssignmentStatus::Delivered);
    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Delivered);
    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Delivered);
    expect($assignment->delivered_at)->not->toBeNull();
});

test('a delivery assignment cannot skip straight from assigned to delivered', function () {
    $shipment = readyShipmentFor(null);
    DeliveryPartner::factory()->create();
    $request = app(CreateDeliveryRequestAction::class)->handle($shipment, 1500);
    $assignment = app(AcceptDeliveryRequestAction::class)->handle($request->notifications->first());

    expect(fn () => app(AdvanceDeliveryAssignmentAction::class)->handle($assignment, DeliveryAssignmentStatus::Delivered))
        ->toThrow(InvalidDeliveryAssignmentTransitionException::class);
});
