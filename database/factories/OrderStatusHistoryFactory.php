<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    protected $model = OrderStatusHistory::class;

    public function definition(): array
    {
        return [
            'historyable_type' => Order::class,
            'historyable_id' => Order::factory(),
            'status' => 'pending_payment',
            'note' => null,
            'changed_by' => null,
        ];
    }
}
