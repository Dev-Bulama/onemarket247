<?php

namespace App\Actions\Inventory\Concerns;

use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\ProductVariation;

/**
 * warehouse_stocks is the source of truth for quantities; products.stock_quantity/
 * product_variations.stock_quantity are a derived cache kept in sync after
 * every mutation, purely so storefront/list queries never have to sum
 * warehouse_stocks at read time (see docs/architecture/02-database-erd.md
 * "Immutable ledgers" — the same derived-cache pattern used for wallet
 * balances). Skipped entirely when manage_stock is false, since the vendor
 * has opted out of quantity tracking for that sellable. Available stock
 * replenishing always clears a stale out-of-stock status back to in_stock;
 * hitting zero only downgrades to out_of_stock — an explicit on_backorder
 * choice is a deliberate vendor decision and is never silently overwritten.
 */
trait RecalculatesSellableStock
{
    private function recalculate(Product|ProductVariation $sellable): void
    {
        if (! $sellable->manage_stock) {
            return;
        }

        $totals = $sellable->warehouseStocks()
            ->selectRaw('COALESCE(SUM(on_hand), 0) as on_hand, COALESCE(SUM(reserved), 0) as reserved')
            ->first();

        $available = max(0, (int) $totals->on_hand - (int) $totals->reserved);

        $status = match (true) {
            $available > 0 => StockStatus::InStock,
            $sellable->stock_status === StockStatus::OnBackorder => StockStatus::OnBackorder,
            default => StockStatus::OutOfStock,
        };

        $sellable->update([
            'stock_quantity' => $available,
            'stock_status' => $status,
        ]);
    }
}
