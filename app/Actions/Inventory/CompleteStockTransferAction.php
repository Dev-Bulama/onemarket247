<?php

namespace App\Actions\Inventory;

use App\Actions\Inventory\Concerns\LocksWarehouseStock;
use App\Actions\Inventory\Concerns\RecalculatesSellableStock;
use App\Actions\Inventory\Concerns\RecordsStockMovement;
use App\Enums\StockMovementBucket;
use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * in_transit → completed: the destination warehouse receives each item —
 * moves the quantity out of `incoming` and into `on_hand`, where it becomes
 * sellable again.
 */
class CompleteStockTransferAction
{
    use LocksWarehouseStock, RecalculatesSellableStock, RecordsStockMovement;

    public function handle(StockTransfer $transfer, ?User $actor = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $actor) {
            /** @var StockTransfer $transfer */
            $transfer = StockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($transfer->status !== StockTransferStatus::InTransit) {
                throw new InvalidArgumentException('Only an in-transit transfer can be completed.');
            }

            foreach ($transfer->items as $item) {
                $product = $item->product_id ? $item->product : null;
                $variation = $item->product_variation_id ? $item->variation : null;
                $sellable = $product ?? $variation;

                $destinationStock = $this->lockedStock($transfer->toWarehouse, $product, $variation);

                if ($destinationStock->incoming < $item->quantity) {
                    throw new InsufficientStockException("Incoming quantity at the destination warehouse is less than expected for item #{$item->id}.");
                }

                $destinationStock->update([
                    'incoming' => $destinationStock->incoming - $item->quantity,
                    'on_hand' => $destinationStock->on_hand + $item->quantity,
                ]);
                $this->recordMovement($destinationStock, StockMovementType::TransferIn, StockMovementBucket::Incoming, -$item->quantity, null, $actor, $transfer);
                $this->recordMovement($destinationStock, StockMovementType::TransferIn, StockMovementBucket::OnHand, $item->quantity, null, $actor, $transfer);

                $this->recalculate($sellable);
            }

            $transfer->update(['status' => StockTransferStatus::Completed, 'completed_at' => now()]);

            return $transfer->fresh('items');
        });
    }
}
