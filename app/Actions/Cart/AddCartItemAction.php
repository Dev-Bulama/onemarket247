<?php

namespace App\Actions\Cart;

use App\Actions\Cart\Concerns\ChecksAvailability;
use App\Enums\ProductType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use RuntimeException;

class AddCartItemAction
{
    use ChecksAvailability;

    public function handle(Cart $cart, Product $product, ?ProductVariation $variation, int $quantity): CartItem
    {
        if ($product->type === ProductType::Variable && ! $variation) {
            throw new RuntimeException('Select a product option first.');
        }

        if ($variation && $variation->product_id !== $product->id) {
            throw new RuntimeException('This option does not belong to the selected product.');
        }

        $sellable = $variation ?? $product;
        $unitPrice = $variation?->price ?? $product->price;

        $existing = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variation_id', $variation?->id)
            ->first();

        $desiredQuantity = ($existing?->quantity ?? 0) + $quantity;

        $this->assertAvailable($sellable, $desiredQuantity);

        if ($existing) {
            $existing->update(['quantity' => $desiredQuantity, 'unit_price' => $unitPrice]);

            return $existing->fresh();
        }

        return $cart->items()->create([
            'product_id' => $product->id,
            'product_variation_id' => $variation?->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
        ]);
    }
}
