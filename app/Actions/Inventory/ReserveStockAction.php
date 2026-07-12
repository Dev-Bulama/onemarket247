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
 * Reserves stock without deducting it yet — the "hold" step of the
 * checkout flow (see docs/architecture/09-lifecycles.md "Checkout → Order
 * Creation"): increments `reserved`, never touches `on_hand`. Throws
 * InsufficientStockException (no overselling) when the requested quantity
 * exceeds on_hand - reserved.
 */
class ReserveStockAction
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

            if (($stock->on_hand - $stock->reserved) < $quantity) {
                throw new InsufficientStockException('Not enough available stock to reserve.');
            }

            $stock->update(['reserved' => $stock->reserved + $quantity]);
            $this->recordMovement($stock, StockMovementType::Reservation, StockMovementBucket::Reserved, $quantity, null, $actor, $reference);
            $this->recalculate($sellable);

            return $stock->fresh();
        });
    }
}
