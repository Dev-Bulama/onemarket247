<?php

namespace App\Actions\Inventory\Concerns;

use App\Enums\StockMovementBucket;
use App\Enums\StockMovementType;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Model;

trait RecordsStockMovement
{
    private function recordMovement(
        WarehouseStock $stock,
        StockMovementType $type,
        StockMovementBucket $bucket,
        int $delta,
        ?string $reason = null,
        ?User $actor = null,
        ?Model $reference = null,
    ): void {
        StockMovement::create([
            'warehouse_stock_id' => $stock->id,
            'type' => $type,
            'bucket' => $bucket,
            'quantity_delta' => $delta,
            'reason' => $reason,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'created_by' => $actor?->id,
        ]);
    }
}
