<?php

namespace App\Actions\Inventory\Concerns;

use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Warehouse;
use App\Models\WarehouseStock;

/**
 * Every stock mutation must run inside DB::transaction() and lock its
 * WarehouseStock row via SELECT ... FOR UPDATE before reading/writing
 * on_hand/reserved/damaged/incoming — see docs/architecture/02-database-erd.md
 * "Concurrency-safe stock". firstOrCreate() itself is not lock-protected
 * (no partial-unique-index is portable across the SQLite/MySQL split this
 * project supports — see the warehouse_stocks migration), so a genuine
 * first-ever-write race on the exact same (warehouse, product|variation) is
 * a known, documented limitation; re-selecting with lockForUpdate()
 * immediately afterward closes the window for every subsequent write.
 */
trait LocksWarehouseStock
{
    private function lockedStock(Warehouse $warehouse, ?Product $product, ?ProductVariation $variation): WarehouseStock
    {
        $attributes = [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product?->id,
            'product_variation_id' => $variation?->id,
        ];

        $stock = WarehouseStock::firstOrCreate($attributes);

        return WarehouseStock::whereKey($stock->id)->lockForUpdate()->firstOrFail();
    }
}
