<?php

namespace App\Actions\Inventory;

use App\Actions\Inventory\Concerns\LocksWarehouseStock;
use App\Actions\Inventory\Concerns\RecalculatesSellableStock;
use App\Actions\Inventory\Concerns\RecordsStockMovement;
use App\Enums\StockMovementBucket;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Restores previously-deducted stock — an approved return or a
 * post-payment cancellation (see docs/architecture/09-lifecycles.md
 * "Approved return → same reversal path"). Increments `on_hand` only;
 * the quantity was never reserved at this point, so `reserved` is untouched.
 */
class RestoreStockAction
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

            $stock->update(['on_hand' => $stock->on_hand + $quantity]);
            $this->recordMovement($stock, StockMovementType::Restoration, StockMovementBucket::OnHand, $quantity, null, $actor, $reference);
            $this->recalculate($sellable);

            return $stock->fresh();
        });
    }
}
