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
 * Moves sellable stock into the `damaged` bucket — it leaves `on_hand`
 * (no longer sellable) without being a sale, return, or transfer.
 */
class ReportDamagedStockAction
{
    use LocksWarehouseStock, RecalculatesSellableStock, RecordsStockMovement;

    public function handle(
        Warehouse $warehouse,
        Product|ProductVariation $sellable,
        int $quantity,
        string $reason,
        ?User $actor = null,
    ): WarehouseStock {
        return DB::transaction(function () use ($warehouse, $sellable, $quantity, $reason, $actor) {
            [$product, $variation] = $sellable instanceof Product ? [$sellable, null] : [null, $sellable];
            $stock = $this->lockedStock($warehouse, $product, $variation);

            if ($stock->on_hand < $quantity) {
                throw new InsufficientStockException('Cannot report more damaged units than are on hand.');
            }

            $stock->update([
                'on_hand' => $stock->on_hand - $quantity,
                'damaged' => $stock->damaged + $quantity,
            ]);
            $this->recordMovement($stock, StockMovementType::DamageReported, StockMovementBucket::OnHand, -$quantity, $reason, $actor);
            $this->recordMovement($stock, StockMovementType::DamageReported, StockMovementBucket::Damaged, $quantity, $reason, $actor);
            $this->recalculate($sellable);

            return $stock->fresh();
        });
    }
}
