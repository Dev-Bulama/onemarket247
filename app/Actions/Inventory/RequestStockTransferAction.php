<?php

namespace App\Actions\Inventory;

use App\Enums\StockTransferStatus;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates a transfer request with no stock movement yet — a paper trail
 * only. DispatchStockTransferAction is the step that actually moves
 * quantities out of the source warehouse.
 *
 * @param  array<int, array{sellable: Product|ProductVariation, quantity: int}>  $items
 */
class RequestStockTransferAction
{
    public function handle(
        Warehouse $from,
        Warehouse $to,
        array $items,
        ?User $requester = null,
        ?string $notes = null,
    ): StockTransfer {
        if ($from->is($to)) {
            throw new InvalidArgumentException('Cannot transfer stock to the same warehouse.');
        }

        if ($from->vendor_id !== $to->vendor_id) {
            throw new InvalidArgumentException('Transfers must be between warehouses of the same vendor.');
        }

        if ($items === []) {
            throw new InvalidArgumentException('A transfer must include at least one item.');
        }

        return DB::transaction(function () use ($from, $to, $items, $requester, $notes) {
            $transfer = StockTransfer::create([
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $to->id,
                'status' => StockTransferStatus::Pending,
                'requested_by' => $requester?->id,
                'notes' => $notes,
                'requested_at' => now(),
            ]);

            foreach ($items as $item) {
                $sellable = $item['sellable'];
                [$productId, $variationId] = $sellable instanceof Product
                    ? [$sellable->id, null]
                    : [null, $sellable->id];

                $transfer->items()->create([
                    'product_id' => $productId,
                    'product_variation_id' => $variationId,
                    'quantity' => $item['quantity'],
                ]);
            }

            return $transfer->fresh('items');
        });
    }
}
