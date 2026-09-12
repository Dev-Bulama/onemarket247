<?php

use App\Enums\OrderStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Support\Analytics\SalesAnalytics;
use Illuminate\Support\Carbon;

test('platform summary counts only revenue-qualifying order statuses', function () {
    Order::factory()->create(['status' => OrderStatus::Paid, 'total' => 10000]);
    Order::factory()->create(['status' => OrderStatus::Completed, 'total' => 5000]);
    Order::factory()->create(['status' => OrderStatus::PendingPayment, 'total' => 99999]);
    Order::factory()->create(['status' => OrderStatus::Cancelled, 'total' => 99999]);
    Order::factory()->create(['status' => OrderStatus::Failed, 'total' => 99999]);

    $summary = app(SalesAnalytics::class)->summary(null, now()->subDay(), now()->addDay());

    expect($summary['revenue'])->toBe(15000)
        ->and($summary['orders'])->toBe(2)
        ->and($summary['average_order_value'])->toBe(7500);
});

test('vendor-scoped summary counts only that vendor\'s qualifying vendor orders', function () {
    $vendor = Vendor::factory()->create();
    $otherVendor = Vendor::factory()->create();

    VendorOrder::factory()->create(['vendor_id' => $vendor->id, 'status' => VendorOrderStatus::Delivered, 'total' => 8000]);
    VendorOrder::factory()->create(['vendor_id' => $vendor->id, 'status' => VendorOrderStatus::Cancelled, 'total' => 99999]);
    VendorOrder::factory()->create(['vendor_id' => $otherVendor->id, 'status' => VendorOrderStatus::Delivered, 'total' => 50000]);

    $summary = app(SalesAnalytics::class)->summary($vendor->id, now()->subDay(), now()->addDay());

    expect($summary['revenue'])->toBe(8000)
        ->and($summary['orders'])->toBe(1);
});

test('summary excludes orders outside the given date range', function () {
    Order::factory()->create(['status' => OrderStatus::Paid, 'total' => 10000, 'created_at' => now()->subDays(10)]);

    $summary = app(SalesAnalytics::class)->summary(null, now()->subDays(2), now());

    expect($summary['revenue'])->toBe(0)
        ->and($summary['orders'])->toBe(0);
});

test('daily revenue fills every day in the range including zero-revenue days', function () {
    $from = Carbon::parse('2026-01-01');
    $to = Carbon::parse('2026-01-03');

    Order::factory()->create(['status' => OrderStatus::Paid, 'total' => 4000, 'created_at' => Carbon::parse('2026-01-01 10:00:00')]);

    $daily = app(SalesAnalytics::class)->dailyRevenue(null, $from, $to);

    expect($daily->all())->toBe([
        '2026-01-01' => 4000,
        '2026-01-02' => 0,
        '2026-01-03' => 0,
    ]);
});
