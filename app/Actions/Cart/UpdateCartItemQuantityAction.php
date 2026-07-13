<?php

namespace App\Actions\Cart;

use App\Actions\Cart\Concerns\ChecksAvailability;
use App\Models\CartItem;

class UpdateCartItemQuantityAction
{
    use ChecksAvailability;

    public function handle(CartItem $item, int $quantity): ?CartItem
    {
        if ($quantity <= 0) {
            $item->delete();

            return null;
        }

        $sellable = $item->variation ?? $item->product;

        $this->assertAvailable($sellable, $quantity);

        $item->update([
            'quantity' => $quantity,
            'unit_price' => $item->currentUnitPrice() ?? $item->unit_price,
        ]);

        return $item->fresh();
    }
}
