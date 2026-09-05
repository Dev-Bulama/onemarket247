<?php

use App\Actions\Order\UpdateVendorOrderStatusAction;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorOrder;

test('the order tracking endpoint includes the vendor order status timeline, oldest first', function () {
    $customer = User::factory()->create();
    $token = $customer->createToken('t', ['customer:*'])->plainTextToken;

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::Confirmed]);

    app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder, VendorOrderStatus::Processing, 'Packing your items');

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/orders/{$order->public_id}/track")
        ->assertOk();

    $histories = $response->json('data.vendor_orders.0.status_histories');

    expect($histories)->toHaveCount(1)
        ->and($histories[0]['status'])->toBe('processing')
        ->and($histories[0]['status_label'])->toBe('Processing')
        ->and($histories[0]['note'])->toBe('Packing your items');
});
