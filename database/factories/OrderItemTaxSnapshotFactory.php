<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemTaxSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemTaxSnapshot>
 */
class OrderItemTaxSnapshotFactory extends Factory
{
    protected $model = OrderItemTaxSnapshot::class;

    public function definition(): array
    {
        $taxable = fake()->numberBetween(1000, 50000);
        $rate = 10;

        return [
            'order_item_id' => OrderItem::factory(),
            'tax_rate_id' => null,
            'rate_percent' => $rate,
            'taxable_amount' => $taxable,
            'tax_amount' => (int) round($taxable * $rate / 100),
        ];
    }
}
