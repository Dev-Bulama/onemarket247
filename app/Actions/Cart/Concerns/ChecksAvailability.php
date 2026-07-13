<?php

namespace App\Actions\Cart\Concerns;

use App\Enums\StockStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductVariation;

trait ChecksAvailability
{
    /**
     * @throws InsufficientStockException
     */
    private function assertAvailable(Product|ProductVariation $sellable, int $quantity): void
    {
        if (! $sellable->manage_stock) {
            return;
        }

        if ($sellable->stock_status === StockStatus::OutOfStock) {
            throw new InsufficientStockException('This product is currently out of stock.');
        }

        if ($sellable->stock_status === StockStatus::InStock && $quantity > $sellable->stock_quantity) {
            throw new InsufficientStockException("Only {$sellable->stock_quantity} left in stock.");
        }
    }
}
