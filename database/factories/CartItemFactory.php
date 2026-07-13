<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_id' => Product::factory(),
            'product_variation_id' => null,
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price' => fake()->numberBetween(1000, 20000),
            'saved_for_later' => false,
        ];
    }

    public function savedForLater(): static
    {
        return $this->state(fn () => ['saved_for_later' => true]);
    }
}
