<?php

use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Support\Analytics\CustomerAnalytics;

test('platform active customers counts distinct customers with a qualifying order', function () {
    $customer = User::factory()->create();
    $repeatOrderCustomer = $customer;

    Order::factory()->create(['customer_id' => $repeatOrderCustomer->id, 'status' => OrderStatus::Paid]);
    Order::factory()->create(['customer_id' => $repeatOrderCustomer->id, 'status' => OrderStatus::Completed]);
    Order::factory()->guest()->create(['status' => OrderStatus::Paid]);
    Order::factory()->create(['status' => OrderStatus::PendingPayment]);

    $count = app(CustomerAnalytics::class)->activeCustomers(null, now()->subDay(), now()->addDay());

    expect($count)->toBe(1);
});

test('platform new customers counts accounts registered in the period', function () {
    User::factory()->create(['user_type' => UserType::Customer, 'created_at' => now()]);
    User::factory()->create(['user_type' => UserType::Customer, 'created_at' => now()->subDays(30)]);

    $count = app(CustomerAnalytics::class)->newCustomers(null, now()->subDay(), now()->addDay());

    expect($count)->toBe(1);
});

test('vendor-scoped active customers only counts buyers of that vendor', function () {
    $vendor = Vendor::factory()->create();
    $otherVendor = Vendor::factory()->create();

    $buyer = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $buyer->id]);
    VendorOrder::factory()->create(['order_id' => $order->id, 'vendor_id' => $vendor->id, 'status' => VendorOrderStatus::Delivered]);

    $otherBuyer = User::factory()->create();
    $otherOrder = Order::factory()->create(['customer_id' => $otherBuyer->id]);
    VendorOrder::factory()->create(['order_id' => $otherOrder->id, 'vendor_id' => $otherVendor->id, 'status' => VendorOrderStatus::Delivered]);

    $count = app(CustomerAnalytics::class)->activeCustomers($vendor->id, now()->subDay(), now()->addDay());

    expect($count)->toBe(1);
});

test('vendor-scoped new customers counts first-time buyers of that vendor in the period', function () {
    $vendor = Vendor::factory()->create();
    $buyer = User::factory()->create();

    $firstOrder = Order::factory()->create(['customer_id' => $buyer->id]);
    VendorOrder::factory()->create([
        'order_id' => $firstOrder->id,
        'vendor_id' => $vendor->id,
        'status' => VendorOrderStatus::Delivered,
        'created_at' => now()->subDays(40),
    ]);

    $secondOrder = Order::factory()->create(['customer_id' => $buyer->id]);
    VendorOrder::factory()->create([
        'order_id' => $secondOrder->id,
        'vendor_id' => $vendor->id,
        'status' => VendorOrderStatus::Delivered,
        'created_at' => now(),
    ]);

    $newInLast30Days = app(CustomerAnalytics::class)->newCustomers($vendor->id, now()->subDays(29), now()->addDay());

    expect($newInLast30Days)->toBe(0);
});
