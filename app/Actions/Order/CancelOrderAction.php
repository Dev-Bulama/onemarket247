<?php

namespace App\Actions\Order;

use App\Exceptions\InvalidOrderTransitionException;
use App\Models\Order;
use App\Models\User;

/**
 * Cancels every vendor sub-order that is still eligible, leaving any that
 * have already progressed past fulfilment alone — a multi-vendor order
 * can be partially cancelled just as it can be partially delivered.
 */
class CancelOrderAction
{
    public function __construct(private readonly CancelVendorOrderAction $cancelVendorOrder) {}

    public function handle(Order $order, string $reason, ?User $actor = null): Order
    {
        $anyCancelled = false;

        foreach ($order->vendorOrders as $vendorOrder) {
            if (in_array($vendorOrder->status, CancelVendorOrderAction::CANCELLABLE_FROM, true)) {
                $this->cancelVendorOrder->handle($vendorOrder, $reason, $actor);
                $anyCancelled = true;
            }
        }

        if (! $anyCancelled) {
            throw new InvalidOrderTransitionException('None of the items in this order can be cancelled anymore.');
        }

        return $order->fresh(['vendorOrders']);
    }
}
