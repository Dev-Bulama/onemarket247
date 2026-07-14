<?php

use App\Actions\Shipping\AssignDeliveryAction;
use App\Actions\Shipping\CreateShipmentAction;
use App\Actions\Shipping\RecordDeliveryEvidenceAction;
use App\Actions\Shipping\RecordShipmentEventAction;
use App\Actions\Shipping\UpdateDeliveryAssignmentStatusAction;
use App\Enums\DeliveryAssignmentStatus;
use App\Enums\DeliveryEvidenceType;
use App\Enums\ShipmentStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Exceptions\ShipmentAlreadyAssignedException;
use App\Models\ShippingCarrier;
use App\Models\VendorOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('creating a shipment for a ready-for-pickup vendor order advances it to shipped', function () {
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::ReadyForPickup]);
    $carrier = ShippingCarrier::factory()->create();

    $shipment = app(CreateShipmentAction::class)->handle($vendorOrder, $carrier, 'TRACK123', null, now()->addDays(3));

    expect($shipment->status)->toBe(ShipmentStatus::Shipped)
        ->and($shipment->tracking_number)->toBe('TRACK123')
        ->and($shipment->shipped_at)->not->toBeNull()
        ->and($shipment->events)->toHaveCount(1);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Shipped);
});

test('creating a shipment for a vendor order that is not ready for pickup is rejected', function () {
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::Processing]);
    $carrier = ShippingCarrier::factory()->create();

    expect(fn () => app(CreateShipmentAction::class)->handle($vendorOrder, $carrier, 'TRACK123', null, null))
        ->toThrow(InvalidOrderTransitionException::class);

    expect($vendorOrder->shipments)->toHaveCount(0);
});

test('recording an in-transit event updates the shipment but not the vendor order', function () {
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::ReadyForPickup]);
    $shipment = app(CreateShipmentAction::class)->handle($vendorOrder, null, null, null, null);

    app(RecordShipmentEventAction::class)->handle($shipment, ShipmentStatus::InTransit, 'Regional hub');

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::InTransit);
    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Shipped);
});

test('recording out_for_delivery then delivered advances the vendor order in step and stamps delivered_at', function () {
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::ReadyForPickup]);
    $shipment = app(CreateShipmentAction::class)->handle($vendorOrder, null, null, null, null);

    app(RecordShipmentEventAction::class)->handle($shipment, ShipmentStatus::OutForDelivery, 'Local depot');
    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::OutForDelivery);

    app(RecordShipmentEventAction::class)->handle($shipment->fresh(), ShipmentStatus::Delivered, 'Front door');
    $delivered = $shipment->fresh();

    expect($delivered->status)->toBe(ShipmentStatus::Delivered)
        ->and($delivered->delivered_at)->not->toBeNull();
    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Delivered);
    expect($delivered->events)->toHaveCount(3);
});

test('a delivery can be assigned exactly once per shipment', function () {
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::ReadyForPickup]);
    $shipment = app(CreateShipmentAction::class)->handle($vendorOrder, null, null, null, null);

    $assignment = app(AssignDeliveryAction::class)->handle($shipment, 'John Rider', '+10000000000');
    expect($assignment->status)->toBe(DeliveryAssignmentStatus::Assigned);

    expect(fn () => app(AssignDeliveryAction::class)->handle($shipment->fresh(), 'Someone Else', null))
        ->toThrow(ShipmentAlreadyAssignedException::class);
});

test('a delivery assignment can be transitioned to delivered, stamping delivered_at', function () {
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::ReadyForPickup]);
    $shipment = app(CreateShipmentAction::class)->handle($vendorOrder, null, null, null, null);
    $assignment = app(AssignDeliveryAction::class)->handle($shipment, 'John Rider', null);

    $updated = app(UpdateDeliveryAssignmentStatusAction::class)->handle($assignment, DeliveryAssignmentStatus::Delivered);

    expect($updated->status)->toBe(DeliveryAssignmentStatus::Delivered)
        ->and($updated->delivered_at)->not->toBeNull();
});

test('photo evidence can be recorded against a delivery assignment and the file is stored', function () {
    Storage::fake('local');

    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::ReadyForPickup]);
    $shipment = app(CreateShipmentAction::class)->handle($vendorOrder, null, null, null, null);
    $assignment = app(AssignDeliveryAction::class)->handle($shipment, 'John Rider', null);

    $file = UploadedFile::fake()->image('proof.jpg');
    $evidence = app(RecordDeliveryEvidenceAction::class)->handle($assignment, DeliveryEvidenceType::Photo, $file, 'Jane Doe', 'Left at front door');

    expect($evidence->type)->toBe(DeliveryEvidenceType::Photo)
        ->and($evidence->recipient_name)->toBe('Jane Doe')
        ->and($evidence->notes)->toBe('Left at front door');
    Storage::disk('local')->assertExists($evidence->file_path);
});
