<?php

namespace Database\Factories;

use App\Enums\OrderNoteVisibility;
use App\Models\Order;
use App\Models\OrderNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderNote>
 */
class OrderNoteFactory extends Factory
{
    protected $model = OrderNote::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'author_id' => null,
            'visibility' => OrderNoteVisibility::Internal,
            'body' => fake()->sentence(),
        ];
    }
}
