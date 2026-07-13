<?php

use App\Actions\Order\UpdateVendorOrderStatusAction;
use App\Enums\OrderNoteVisibility;
use App\Enums\UserType;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\VendorOrder;

test('a customer can view their order history', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $order = Order::factory()->create(['customer_id' => $user->id]);
    VendorOrder::factory()->create(['order_id' => $order->id]);

    $this->actingAs($user)->get(route('account.orders.index'))
        ->assertOk()
        ->assertSee($order->order_number);
});

test('a customer can view their own order detail page', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $order = Order::factory()->create(['customer_id' => $user->id]);
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id]);

    $order->notes()->create([
        'visibility' => OrderNoteVisibility::Customer,
        'body' => 'Your order is being packed.',
    ]);
    $order->notes()->create([
        'visibility' => OrderNoteVisibility::Internal,
        'body' => 'Internal only note.',
    ]);

    $response = $this->actingAs($user)->get(route('account.orders.show', $order))
        ->assertOk()
        ->assertSee($vendorOrder->vendor_order_number)
        ->assertSee('Your order is being packed.');

    $response->assertDontSee('Internal only note.');
});

test('a customer cannot view another customer order', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $otherOrder = Order::factory()->create();

    $this->actingAs($user)->get(route('account.orders.show', $otherOrder))->assertForbidden();
});

test('a customer can cancel an eligible order', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $order = Order::factory()->create(['customer_id' => $user->id]);
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'status' => VendorOrderStatus::Confirmed,
    ]);

    $this->actingAs($user)->post(route('account.orders.cancel', $order), [
        'reason' => 'Changed my mind',
    ])->assertRedirect(route('account.orders.show', $order));

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Cancelled);
});

test('a customer cannot cancel an order with nothing left to cancel', function () {
    $user = User::factory()->create(['user_type' => UserType::Customer, 'email_verified_at' => now()]);
    $order = Order::factory()->create(['customer_id' => $user->id]);
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'status' => VendorOrderStatus::Confirmed,
    ]);

    app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder, VendorOrderStatus::Processing);
    app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder->fresh(), VendorOrderStatus::ReadyForPickup);
    app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder->fresh(), VendorOrderStatus::Shipped);
    app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder->fresh(), VendorOrderStatus::OutForDelivery);
    app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder->fresh(), VendorOrderStatus::Delivered);

    $this->actingAs($user)->post(route('account.orders.cancel', $order), [
        'reason' => 'Too late',
    ])->assertRedirect()
        ->assertSessionHasErrors('cancel');

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Delivered);
});

test('a guest cannot access account order pages', function () {
    $order = Order::factory()->create();

    $this->get(route('account.orders.index'))->assertRedirect(route('login'));
    $this->get(route('account.orders.show', $order))->assertRedirect(route('login'));
});
