<?php

use App\Actions\Shipping\CreateShipmentAction;
use App\Actions\Shipping\RecordShipmentEventAction;
use App\Enums\ShipmentStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\ShippingCarrier;
use App\Models\User;
use App\Models\VendorOrder;

test('a customer can view the tracking page for their own order, including carrier link and event timeline', function () {
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::ReadyForPickup]);
    $carrier = ShippingCarrier::factory()->create(['name' => 'Speedy Post', 'tracking_url_template' => 'https://track.test/{tracking_number}']);
    $shipment = app(CreateShipmentAction::class)->handle($vendorOrder, $carrier, 'CTRACK1', null, now()->addDays(2));
    app(RecordShipmentEventAction::class)->handle($shipment, ShipmentStatus::InTransit, 'Regional hub', 'Package scanned');

    $customer = User::factory()->create();
    $order->update(['customer_id' => $customer->id]);

    $this->actingAs($customer)
        ->get(route('account.orders.track', $order))
        ->assertOk()
        ->assertSee('Speedy Post')
        ->assertSee('CTRACK1')
        ->assertSee('https://track.test/CTRACK1', false)
        ->assertSee('Package scanned')
        ->assertSee($vendorOrder->vendor_order_number);
});

test('a vendor order with no shipment yet shows "Not yet shipped" on the tracking page', function () {
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id]);

    $customer = User::factory()->create();
    $order->update(['customer_id' => $customer->id]);

    $this->actingAs($customer)
        ->get(route('account.orders.track', $order))
        ->assertOk()
        ->assertSee('Not yet shipped')
        ->assertSee($vendorOrder->vendor_order_number);
});

test('a customer cannot view another customer\'s tracking page', function () {
    $order = Order::factory()->create();
    $owner = User::factory()->create();
    $order->update(['customer_id' => $owner->id]);

    $other = User::factory()->create();

    $this->actingAs($other)->get(route('account.orders.track', $order))->assertForbidden();
});

test('a guest is redirected to login when trying to view a tracking page', function () {
    $order = Order::factory()->create();
    $owner = User::factory()->create();
    $order->update(['customer_id' => $owner->id]);

    $this->get(route('account.orders.track', $order))->assertRedirect();
});
