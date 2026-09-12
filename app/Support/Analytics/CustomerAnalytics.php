<?php

namespace App\Support\Analytics;

use App\Enums\OrderStatus;
use App\Enums\UserType;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\VendorOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Customer analytics shared by the admin (platform-wide, $vendorId = null)
 * and vendor ($vendorId = the vendor's own id) dashboards. "New customer"
 * means two different things depending on scope, since vendors don't have
 * their own customer registrations: platform-wide it's a new account
 * sign-up; vendor-scoped it's a customer buying from that vendor for the
 * first time ever.
 */
class CustomerAnalytics
{
    private const array EXCLUDED_ORDER_STATUSES = [
        OrderStatus::PendingPayment,
        OrderStatus::PaymentProcessing,
        OrderStatus::Cancelled,
        OrderStatus::Failed,
    ];

    private const array EXCLUDED_VENDOR_ORDER_STATUSES = [
        VendorOrderStatus::PendingPayment,
        VendorOrderStatus::Cancelled,
    ];

    /**
     * Distinct customers who placed a qualifying order in the period.
     */
    public function activeCustomers(?int $vendorId, Carbon $from, Carbon $to): int
    {
        if ($vendorId !== null) {
            return VendorOrder::query()
                ->where('vendor_orders.vendor_id', $vendorId)
                ->whereNotIn('vendor_orders.status', self::EXCLUDED_VENDOR_ORDER_STATUSES)
                ->whereBetween('vendor_orders.created_at', [$from, $to])
                ->join('orders', 'orders.id', '=', 'vendor_orders.order_id')
                ->whereNotNull('orders.customer_id')
                ->distinct()
                ->count('orders.customer_id');
        }

        return Order::query()
            ->whereNotIn('status', self::EXCLUDED_ORDER_STATUSES)
            ->whereNotNull('customer_id')
            ->whereBetween('created_at', [$from, $to])
            ->distinct()
            ->count('customer_id');
    }

    /**
     * Platform-wide: new registered customer accounts in the period.
     * Vendor-scoped: customers whose first-ever qualifying order with this
     * vendor falls in the period (first-time buyers).
     */
    public function newCustomers(?int $vendorId, Carbon $from, Carbon $to): int
    {
        if ($vendorId !== null) {
            $firstOrders = VendorOrder::query()
                ->where('vendor_orders.vendor_id', $vendorId)
                ->whereNotIn('vendor_orders.status', self::EXCLUDED_VENDOR_ORDER_STATUSES)
                ->join('orders', 'orders.id', '=', 'vendor_orders.order_id')
                ->whereNotNull('orders.customer_id')
                ->selectRaw('orders.customer_id as customer_id, MIN(vendor_orders.created_at) as first_order_at')
                ->groupBy('orders.customer_id');

            return DB::query()
                ->fromSub($firstOrders, 'first_orders')
                ->whereBetween('first_order_at', [$from, $to])
                ->count();
        }

        return User::query()
            ->where('user_type', UserType::Customer)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }
}
