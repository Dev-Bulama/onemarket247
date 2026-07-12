<?php

namespace App\Actions\Inventory;

use App\Actions\Inventory\Concerns\LocksWarehouseStock;
use App\Actions\Inventory\Concerns\RecalculatesSellableStock;
use App\Actions\Inventory\Concerns\RecordsStockMovement;
use App\Enums\StockMovementBucket;
use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Cancels a transfer that has not been completed yet. A still-pending
 * transfer has moved no stock, so cancelling it is a pure status change;
 * an in-transit transfer already left the source warehouse, so cancelling
 * it reverses that dispatch (source on_hand restored, destination incoming
 * cleared) before marking it cancelled.
 */
class CancelStockTransferAction
{
    use LocksWarehouseStock, RecalculatesSellableStock, RecordsStockMovement;

    public function handle(StockTransfer $transfer, ?User $actor = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $actor) {
            /** @var StockTransfer $transfer */
            $transfer = StockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if (! in_array($transfer->status, [StockTransferStatus::Pending, StockTransferStatus::InTransit], true)) {
                throw new InvalidArgumentException('Only a pending or in-transit transfer can be cancelled.');
            }

            if ($transfer->status === StockTransferStatus::InTransit) {
                foreach ($transfer->items as $item) {
                    $product = $item->product_id ? $item->product : null;
                    $variation = $item->product_variation_id ? $item->variation : null;
                    $sellable = $product ?? $variation;

                    $sourceStock = $this->lockedStock($transfer->fromWarehouse, $product, $variation);
                    $sourceStock->update(['on_hand' => $sourceStock->on_hand + $item->quantity]);
                    $this->recordMovement($sourceStock, StockMovementType::TransferCancelled, StockMovementBucket::OnHand, $item->quantity, null, $actor, $transfer);

                    $destinationStock = $this->lockedStock($transfer->toWarehouse, $product, $variation);
                    $destinationStock->update(['incoming' => $destinationStock->incoming - $item->quantity]);
                    $this->recordMovement($destinationStock, StockMovementType::TransferCancelled, StockMovementBucket::Incoming, -$item->quantity, null, $actor, $transfer);

                    $this->recalculate($sellable);
                }
            }

            $transfer->update(['status' => StockTransferStatus::Cancelled]);

            return $transfer->fresh('items');
        });
    }
}
