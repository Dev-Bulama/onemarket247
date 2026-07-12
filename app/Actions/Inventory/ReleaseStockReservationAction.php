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
 * Releases a hold without deducting stock — cancellation before payment
 * (see docs/architecture/09-lifecycles.md "Cancellation / Stock
 * Restoration"). Decrements `reserved` only; `on_hand` is untouched since
 * nothing was ever removed from it.
 */
class ReleaseStockReservationAction
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

            if ($stock->reserved < $quantity) {
                throw new InsufficientStockException('Cannot release more than is currently reserved.');
            }

            $stock->update(['reserved' => $stock->reserved - $quantity]);
            $this->recordMovement($stock, StockMovementType::Release, StockMovementBucket::Reserved, -$quantity, null, $actor, $reference);
            $this->recalculate($sellable);

            return $stock->fresh();
        });
    }
}
