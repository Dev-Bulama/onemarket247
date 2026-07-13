<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use Illuminate\Support\Collection;

/**
 * The parent Order's status is always derived from its child VendorOrders,
 * never hand-set independently (see docs/architecture/09-lifecycles.md
 * "Order Lifecycle" — "it is never hand-set independently of children,
 * preventing drift"). Phase 11 only ever creates orders whose children all
 * start at pending_payment, so this is exercised end-to-end for that one
 * case here; the richer transitions (confirmed, processing, shipped, ...)
 * only become reachable once Phase 12 adds the actions that move a
 * VendorOrder through its own lifecycle, at which point this class is the
 * single place those transitions get reflected onto the parent.
 */
class OrderStatusAggregator
{
    private const DELIVERED_LIKE = [VendorOrderStatus::Delivered, VendorOrderStatus::Completed];

    private const IN_PROGRESS = [
        VendorOrderStatus::Confirmed,
        VendorOrderStatus::Processing,
        VendorOrderStatus::ReadyForPickup,
        VendorOrderStatus::Shipped,
        VendorOrderStatus::OutForDelivery,
    ];

    public function aggregate(Order $order): OrderStatus
    {
        /** @var Collection<int, VendorOrderStatus> $statuses */
        $statuses = $order->vendorOrders()->pluck('status');

        if ($statuses->isEmpty() || $statuses->every(fn (VendorOrderStatus $s) => $s === VendorOrderStatus::PendingPayment)) {
            return OrderStatus::PendingPayment;
        }

        if ($statuses->every(fn (VendorOrderStatus $s) => $s === VendorOrderStatus::Cancelled)) {
            return OrderStatus::Cancelled;
        }

        if ($statuses->contains(VendorOrderStatus::Disputed)) {
            return OrderStatus::Disputed;
        }

        if ($statuses->every(fn (VendorOrderStatus $s) => in_array($s, self::DELIVERED_LIKE, true))) {
            return OrderStatus::Completed;
        }

        $anyDelivered = $statuses->contains(fn (VendorOrderStatus $s) => in_array($s, self::DELIVERED_LIKE, true));
        $anyNotDelivered = $statuses->contains(fn (VendorOrderStatus $s) => ! in_array($s, self::DELIVERED_LIKE, true));

        if ($anyDelivered && $anyNotDelivered) {
            return OrderStatus::PartiallyDelivered;
        }

        if ($statuses->every(fn (VendorOrderStatus $s) => in_array($s, self::IN_PROGRESS, true))) {
            return OrderStatus::Processing;
        }

        return OrderStatus::Confirmed;
    }

    public function recompute(Order $order): Order
    {
        $order->update(['status' => $this->aggregate($order)]);

        return $order->fresh();
    }
}
