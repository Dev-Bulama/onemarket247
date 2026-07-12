<?php

namespace App\Actions\Inventory;

use App\Actions\Inventory\Concerns\LocksWarehouseStock;
use App\Actions\Inventory\Concerns\RecalculatesSellableStock;
use App\Actions\Inventory\Concerns\RecordsStockMovement;
use App\Enums\StockMovementBucket;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Converts a reservation into a hard deduction — payment confirmed (see
 * docs/architecture/09-lifecycles.md "On verified success ... stock
 * reservation converted to a hard deduction"). Removes the quantity from
 * both `reserved` and `on_hand` together, since a deduction always follows
 * an existing reservation for that same quantity.
 */
class DeductStockAction
{
    use LocksWarehouseStock, RecalculatesSellableStock, RecordsStockMovement;

    public function handle(
        Warehouse $warehouse,
        Product|ProductVariation $sellable,
        int $quantity,
        ?User $actor = null,
        ?Model $reference = null,
    ): WarehouseStock {
        return DB::transaction(function () use ($warehouse, $sellable, $quantity, $actor, $reference) {
            [$product, $variation] = $sellable instanceof Product ? [$sellable, null] : [null, $sellable];
            $stock = $this->lockedStock($warehouse, $product, $variation);

            if ($stock->reserved < $quantity || $stock->on_hand < $quantity) {
                throw new InsufficientStockException('Cannot deduct more than is currently reserved and on hand.');
            }

            $stock->update([
                'on_hand' => $stock->on_hand - $quantity,
                'reserved' => $stock->reserved - $quantity,
            ]);
            $this->recordMovement($stock, StockMovementType::Deduction, StockMovementBucket::OnHand, -$quantity, null, $actor, $reference);
            $this->recordMovement($stock, StockMovementType::Deduction, StockMovementBucket::Reserved, -$quantity, null, $actor, $reference);
            $this->recalculate($sellable);

            return $stock->fresh();
        });
    }
}
