<?php

namespace App\Actions\Order;

use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\User;
use App\Models\VendorOrder;
use App\Notifications\VendorOrderStatusUpdatedNotification;
use App\Services\Order\OrderStatusAggregator;
use Illuminate\Support\Facades\DB;

/**
 * Moves a vendor order forward through its own fulfilment lifecycle (see
 * docs/architecture/09-lifecycles.md "Order Lifecycle"). Cancellation is
 * deliberately not reachable through this action — it has its own side
 * effects (releasing reserved stock) and its own action,
 * CancelVendorOrderAction. Returned/Refunded/Disputed aren't reachable
 * yet either: those need Phase 18's return/dispute workflow to set them
 * meaningfully, so this phase only implements the happy-path fulfilment
 * progression plus an on-hold branch.
 */
class UpdateVendorOrderStatusAction
{
    public const ALLOWED_TRANSITIONS = [
        'pending_payment' => [VendorOrderStatus::Confirmed],
        'confirmed' => [VendorOrderStatus::Processing, VendorOrderStatus::OnHold],
        'processing' => [VendorOrderStatus::ReadyForPickup, VendorOrderStatus::OnHold],
        'on_hold' => [VendorOrderStatus::Processing],
        'ready_for_pickup' => [VendorOrderStatus::Shipped],
        'shipped' => [VendorOrderStatus::OutForDelivery],
        'out_for_delivery' => [VendorOrderStatus::Delivered],
        'delivered' => [VendorOrderStatus::Completed],
    ];

    public function __construct(private readonly OrderStatusAggregator $aggregator) {}

    /**
     * @return array<int, VendorOrderStatus>
     */
    public static function nextStatusesFor(VendorOrderStatus $current): array
    {
        return self::ALLOWED_TRANSITIONS[$current->value] ?? [];
    }

    public function handle(VendorOrder $vendorOrder, VendorOrderStatus $newStatus, ?string $note = null, ?User $actor = null): VendorOrder
    {
        $allowed = self::ALLOWED_TRANSITIONS[$vendorOrder->status->value] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw new InvalidOrderTransitionException(
                "Cannot move a vendor order from \"{$vendorOrder->status->getLabel()}\" to \"{$newStatus->getLabel()}\"."
            );
        }

        $vendorOrder = DB::transaction(function () use ($vendorOrder, $newStatus, $note, $actor) {
            $vendorOrder->update(['status' => $newStatus]);

            $vendorOrder->statusHistories()->create([
                'status' => $newStatus->value,
                'note' => $note,
                'changed_by' => $actor?->id,
            ]);

            $this->aggregator->recompute($vendorOrder->order);

            return $vendorOrder->fresh();
        });

        $vendorOrder->order->notifiable()->notify(new VendorOrderStatusUpdatedNotification($vendorOrder));

        return $vendorOrder;
    }
}
