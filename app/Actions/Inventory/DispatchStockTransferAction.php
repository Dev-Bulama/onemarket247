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
 * pending → in_transit: removes each item's quantity from the source
 * warehouse's on_hand and adds it to the destination warehouse's incoming
 * bucket. Stock is not sellable from the destination until
 * CompleteStockTransferAction receives it.
 */
class DispatchStockTransferAction
{
    use LocksWarehouseStock, RecalculatesSellableStock, RecordsStockMovement;

    public function handle(StockTransfer $transfer, ?User $actor = null): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $actor) {
            /** @var StockTransfer $transfer */
            $transfer = StockTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();

            if ($transfer->status !== StockTransferStatus::Pending) {
                throw new InvalidArgumentException('Only a pending transfer can be dispatched.');
            }

            foreach ($transfer->items as $item) {
                $product = $item->product_id ? $item->product : null;
                $variation = $item->product_variation_id ? $item->variation : null;
                $sellable = $product ?? $variation;

                $sourceStock = $this->lockedStock($transfer->fromWarehouse, $product, $variation);

                if ($sourceStock->on_hand < $item->quantity) {
                    throw new InsufficientStockException("Insufficient on-hand stock at the source warehouse for item #{$item->id}.");
                }

                $sourceStock->update(['on_hand' => $sourceStock->on_hand - $item->quantity]);
                $this->recordMovement($sourceStock, StockMovementType::TransferOut, StockMovementBucket::OnHand, -$item->quantity, null, $actor, $transfer);

                $destinationStock = $this->lockedStock($transfer->toWarehouse, $product, $variation);
                $destinationStock->update(['incoming' => $destinationStock->incoming + $item->quantity]);
                $this->recordMovement($destinationStock, StockMovementType::TransferIn, StockMovementBucket::Incoming, $item->quantity, null, $actor, $transfer);

                $this->recalculate($sellable);
            }

            $transfer->update(['status' => StockTransferStatus::InTransit]);

            return $transfer->fresh('items');
        });
    }
}
