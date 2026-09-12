<?php

namespace App\Support\Analytics;

use App\Enums\OrderStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\VendorOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Revenue/order-count analytics, shared by the admin (platform-wide,
 * $vendorId = null, queries Order) and vendor ($vendorId = the vendor's
 * own id, queries VendorOrder) analytics dashboards. The two order tables
 * have their own status enums, so the "counts as revenue" exclusion list
 * is defined separately for each — a platform Order can be Paid while its
 * per-vendor split still hasn't reached an equivalent status, and vice
 * versa isn't possible, but the enums simply don't share values.
 */
class SalesAnalytics
{
    /**
     * @var list<OrderStatus>
     */
    private const array EXCLUDED_ORDER_STATUSES = [
        OrderStatus::PendingPayment,
        OrderStatus::PaymentProcessing,
        OrderStatus::Cancelled,
        OrderStatus::Failed,
    ];

    /**
     * @var list<VendorOrderStatus>
     */
    private const array EXCLUDED_VENDOR_ORDER_STATUSES = [
        VendorOrderStatus::PendingPayment,
        VendorOrderStatus::Cancelled,
    ];

    /**
     * @return array{revenue: int, orders: int, average_order_value: int}
     */
    public function summary(?int $vendorId, Carbon $from, Carbon $to): array
    {
        $rows = $this->baseQuery($vendorId, $from, $to)->get(['total']);

        $revenue = (int) $rows->sum('total');
        $orders = $rows->count();

        return [
            'revenue' => $revenue,
            'orders' => $orders,
            'average_order_value' => $orders > 0 ? intdiv($revenue, $orders) : 0,
        ];
    }

    /**
     * Day => revenue (minor units), every day in range present even if 0.
     *
     * @return Collection<string, int>
     */
    public function dailyRevenue(?int $vendorId, Carbon $from, Carbon $to): Collection
    {
        $rows = $this->baseQuery($vendorId, $from, $to)
            ->selectRaw('DATE(created_at) as date, SUM(total) as revenue')
            ->groupBy('date')
            ->pluck('revenue', 'date');

        return $this->fillDateRange($from, $to, $rows);
    }

    private function baseQuery(?int $vendorId, Carbon $from, Carbon $to): Builder
    {
        if ($vendorId !== null) {
            return VendorOrder::query()
                ->where('vendor_id', $vendorId)
                ->whereNotIn('status', self::EXCLUDED_VENDOR_ORDER_STATUSES)
                ->whereBetween('created_at', [$from, $to]);
        }

        return Order::query()
            ->whereNotIn('status', self::EXCLUDED_ORDER_STATUSES)
            ->whereBetween('created_at', [$from, $to]);
    }

    /**
     * @return Collection<string, int>
     */
    public function fillDateRange(Carbon $from, Carbon $to, Collection $values): Collection
    {
        $filled = new Collection;

        for ($day = $from->copy()->startOfDay(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $filled->put($key, (int) ($values[$key] ?? 0));
        }

        return $filled;
    }
}
