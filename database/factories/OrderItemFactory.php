<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\VendorOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->numberBetween(500, 20000);

        return [
            'vendor_order_id' => VendorOrder::factory(),
            'product_id' => Product::factory(),
            'product_variation_id' => null,
            'product_name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->bothify('SKU-########')),
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => $unitPrice * $quantity,
        ];
    }
}
