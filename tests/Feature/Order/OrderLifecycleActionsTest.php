<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\ReserveStockAction;
use App\Actions\Order\AddOrderNoteAction;
use App\Actions\Order\CancelOrderAction;
use App\Actions\Order\CancelVendorOrderAction;
use App\Actions\Order\UpdateVendorOrderStatusAction;
use App\Enums\OrderNoteVisibility;
use App\Enums\OrderStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Notifications\VendorOrderCancelledNotification;
use App\Notifications\VendorOrderStatusUpdatedNotification;
use Illuminate\Support\Facades\Notification;

test('a vendor order can move through its allowed forward transitions', function () {
    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'status' => VendorOrderStatus::PendingPayment,
    ]);

    $vendorOrder = app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder, VendorOrderStatus::Confirmed);
    expect($vendorOrder->status)->toBe(VendorOrderStatus::Confirmed);

    $vendorOrder = app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder, VendorOrderStatus::Processing, 'Packing.');
    expect($vendorOrder->status)->toBe(VendorOrderStatus::Processing);

    $history = $vendorOrder->statusHistories()->latest('id')->first();
    expect($history->status)->toBe('processing')
        ->and($history->note)->toBe('Packing.');
});

test('an invalid vendor order transition is rejected', function () {
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::PendingPayment]);

    expect(fn () => app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder, VendorOrderStatus::Shipped))
        ->toThrow(InvalidOrderTransitionException::class);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::PendingPayment);
});

test('updating a vendor order status recomputes the parent order status', function () {
    $order = Order::factory()->create();
    $vendorOrderA = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::PendingPayment]);
    $vendorOrderB = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::PendingPayment]);

    // One child still pending payment, the other confirmed: not every child
    // is in the aggregator's IN_PROGRESS set yet, so the parent stays at
    // its "some progress, not all" fallback of Confirmed.
    app(UpdateVendorOrderStatusAction::class)->handle($vendorOrderA, VendorOrderStatus::Confirmed);
    expect($order->fresh()->status)->toBe(OrderStatus::Confirmed);

    // Both children now in the IN_PROGRESS set: parent rolls up to Processing.
    app(UpdateVendorOrderStatusAction::class)->handle($vendorOrderB, VendorOrderStatus::Confirmed);
    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

test('cancelling a vendor order releases its reserved stock and recomputes the parent order', function () {
    $vendor = Vendor::factory()->create();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    $order = Order::factory()->create();
    $vendorOrder = VendorOrder::factory()->create([
        'order_id' => $order->id,
        'vendor_id' => $vendor->id,
        'status' => VendorOrderStatus::Confirmed,
    ]);
    OrderItem::factory()->create([
        'vendor_order_id' => $vendorOrder->id,
        'product_id' => $product->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 4,
    ]);

    app(ReserveStockAction::class)->handle($warehouse, $product, 4, null, $vendorOrder);
    $stock = WarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail();
    expect($stock->reserved)->toBe(4);

    $actor = User::factory()->create();
    $cancelled = app(CancelVendorOrderAction::class)->handle($vendorOrder, 'Vendor out of stock.', $actor);

    expect($cancelled->status)->toBe(VendorOrderStatus::Cancelled)
        ->and($stock->fresh()->reserved)->toBe(0)
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled);

    $history = $cancelled->statusHistories()->latest('id')->first();
    expect($history->note)->toBe('Vendor out of stock.')
        ->and($history->changed_by)->toBe($actor->id);
});

test('a vendor order dispatched for fulfilment can no longer be cancelled', function () {
    $vendorOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::Shipped]);

    expect(fn () => app(CancelVendorOrderAction::class)->handle($vendorOrder, 'Too late.'))
        ->toThrow(InvalidOrderTransitionException::class);

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Shipped);
});

test('cancelling an order cancels only the vendor orders still eligible', function () {
    $order = Order::factory()->create();
    $cancellable = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::Confirmed]);
    $dispatched = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::Shipped]);

    $result = app(CancelOrderAction::class)->handle($order, 'Customer changed their mind.');

    expect($cancellable->fresh()->status)->toBe(VendorOrderStatus::Cancelled)
        ->and($dispatched->fresh()->status)->toBe(VendorOrderStatus::Shipped)
        ->and($result->vendorOrders)->toHaveCount(2);
});

test('cancelling an order with nothing left to cancel throws', function () {
    $order = Order::factory()->create();
    VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::Delivered]);

    expect(fn () => app(CancelOrderAction::class)->handle($order, 'Too late.'))
        ->toThrow(InvalidOrderTransitionException::class);
});

test('a note can be added to an order with a given visibility', function () {
    $order = Order::factory()->create();
    $author = User::factory()->create();

    $note = app(AddOrderNoteAction::class)->handle($order, 'Delayed due to weather.', OrderNoteVisibility::Customer, $author);

    expect($note->body)->toBe('Delayed due to weather.')
        ->and($note->visibility)->toBe(OrderNoteVisibility::Customer)
        ->and($note->author_id)->toBe($author->id)
        ->and($order->notes()->count())->toBe(1);
});

test('a registered customer is notified when their vendor order status changes', function () {
    Notification::fake();

    $customer = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::PendingPayment]);

    app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder, VendorOrderStatus::Confirmed);

    Notification::assertSentTo($customer, VendorOrderStatusUpdatedNotification::class);
});

test('a guest is notified by email when their vendor order status changes', function () {
    Notification::fake();

    $order = Order::factory()->guest()->create();
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::PendingPayment]);

    app(UpdateVendorOrderStatusAction::class)->handle($vendorOrder, VendorOrderStatus::Confirmed);

    Notification::assertSentOnDemand(
        VendorOrderStatusUpdatedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $order->guest_email,
    );
});

test('a registered customer is notified when their vendor order is cancelled', function () {
    Notification::fake();

    $customer = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::Confirmed]);

    app(CancelVendorOrderAction::class)->handle($vendorOrder, 'Vendor is out of stock.');

    Notification::assertSentTo($customer, VendorOrderCancelledNotification::class);
});
