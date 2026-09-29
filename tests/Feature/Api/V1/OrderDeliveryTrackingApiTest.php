<?php

use App\Actions\Shipping\AssignDeliveryAction;
use App\Actions\Shipping\CreateShipmentAction;
use App\Enums\DeliveryAssignmentStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;

test('the order tracking API exposes the assigned delivery partner once one accepts', function () {
    $user = User::factory()->create();
    $token = $user->createToken('t', ['customer:*'])->plainTextToken;

    $order = Order::factory()->create(['customer_id' => $user->id]);
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::ReadyForPickup]);
    $shipment = app(CreateShipmentAction::class)->handle($vendorOrder, null, null, null, null);
    app(AssignDeliveryAction::class)->handle($shipment, 'John Rider', '+10000000000');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/orders/{$order->public_id}/track");

    $response->assertOk()
        ->assertJsonPath('data.vendor_orders.0.delivery.partner_name', 'John Rider')
        ->assertJsonPath('data.vendor_orders.0.delivery.status', DeliveryAssignmentStatus::Assigned->value);
});

test('the order tracking API shows no delivery info when no partner has accepted yet', function () {
    $user = User::factory()->create();
    $token = $user->createToken('t', ['customer:*'])->plainTextToken;

    $order = Order::factory()->create(['customer_id' => $user->id]);
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::ReadyForPickup]);
    app(CreateShipmentAction::class)->handle($vendorOrder, null, null, null, null);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/orders/{$order->public_id}/track");

    $response->assertOk()->assertJsonPath('data.vendor_orders.0.delivery', null);
});

test('the vendor order detail API also exposes the assigned delivery partner', function () {
    $vendor = Vendor::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['vendor_id' => $vendor->id, 'status' => VendorOrderStatus::ReadyForPickup]);
    $shipment = app(CreateShipmentAction::class)->handle($vendorOrder, null, null, null, null);
    app(AssignDeliveryAction::class)->handle($shipment, 'John Rider', '+10000000000');

    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/vendor/orders/{$vendorOrder->id}");

    $response->assertOk()
        ->assertJsonPath('data.delivery.partner_name', 'John Rider')
        ->assertJsonPath('data.delivery.status', DeliveryAssignmentStatus::Assigned->value);
});
