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
use Illuminate\Support\Facades\DB;

/**
 * A manual on-hand correction (recount, restock, shrinkage) — the only
 * action that lets on_hand move by an arbitrary signed delta outside the
 * reserve/deduct/restore lifecycle.
 */
class AdjustStockAction
{
    use LocksWarehouseStock, RecalculatesSellableStock, RecordsStockMovement;

    public function handle(
        Warehouse $warehouse,
        Product|ProductVariation $sellable,
        int $delta,
        string $reason,
        ?User $actor = null,
    ): WarehouseStock {
        return DB::transaction(function () use ($warehouse, $sellable, $delta, $reason, $actor) {
            [$product, $variation] = $sellable instanceof Product ? [$sellable, null] : [null, $sellable];
            $stock = $this->lockedStock($warehouse, $product, $variation);

            $newOnHand = $stock->on_hand + $delta;

            if ($newOnHand < 0) {
                throw new InsufficientStockException('Adjustment would result in negative on-hand stock.');
            }

            $stock->update(['on_hand' => $newOnHand]);
            $this->recordMovement($stock, StockMovementType::Adjustment, StockMovementBucket::OnHand, $delta, $reason, $actor);
            $this->recalculate($sellable);

            return $stock->fresh();
        });
    }
}
