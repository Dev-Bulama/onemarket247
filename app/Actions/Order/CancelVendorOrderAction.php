<?php

namespace App\Actions\Order;

use App\Actions\Inventory\ReleaseStockReservationAction;
use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\User;
use App\Models\VendorOrder;
use App\Notifications\VendorOrderCancelledNotification;
use App\Services\Order\OrderStatusAggregator;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Customer/vendor/admin cancellation before fulfilment" from
 * docs/architecture/09-lifecycles.md "Cancellation / Stock Restoration":
 * reverses every reserved item back onto its warehouse before flipping the
 * status, never the other way round. Wallet/commission voiding (mentioned
 * in the same lifecycle section) isn't implemented yet — those ledgers
 * don't exist until Phase 14.
 */
class CancelVendorOrderAction
{
    public const CANCELLABLE_FROM = [
        VendorOrderStatus::PendingPayment,
        VendorOrderStatus::Confirmed,
        VendorOrderStatus::Processing,
        VendorOrderStatus::OnHold,
    ];

    public function __construct(private readonly OrderStatusAggregator $aggregator) {}

    public function handle(VendorOrder $vendorOrder, string $reason, ?User $actor = null): VendorOrder
    {
        if (! in_array($vendorOrder->status, self::CANCELLABLE_FROM, true)) {
            throw new InvalidOrderTransitionException('This order can no longer be cancelled once it has been dispatched for fulfilment.');
        }

        $vendorOrder = DB::transaction(function () use ($vendorOrder, $reason, $actor) {
            foreach ($vendorOrder->orderItems as $item) {
                if ($item->warehouse_id === null) {
                    continue;
                }

                app(ReleaseStockReservationAction::class)->handle($item->warehouse, $item->sellable(), $item->quantity, $actor, $vendorOrder);
            }

            $vendorOrder->update(['status' => VendorOrderStatus::Cancelled]);

            $vendorOrder->statusHistories()->create([
                'status' => VendorOrderStatus::Cancelled->value,
                'note' => $reason,
                'changed_by' => $actor?->id,
            ]);

            $this->aggregator->recompute($vendorOrder->order);

            return $vendorOrder->fresh();
        });

        // The cancellation already committed above — a mail transport
        // failure here must not turn a successful cancellation into a 500.
        try {
            $vendorOrder->order->notifiable()->notify(new VendorOrderCancelledNotification($vendorOrder, $reason));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $vendorOrder;
    }
}
