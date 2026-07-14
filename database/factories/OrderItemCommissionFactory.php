<?php

namespace Database\Factories;

use App\Enums\CommissionType;
use App\Models\OrderItem;
use App\Models\OrderItemCommission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemCommission>
 */
class OrderItemCommissionFactory extends Factory
{
    protected $model = OrderItemCommission::class;

    public function definition(): array
    {
        $gross = fake()->numberBetween(1000, 50000);
        $commission = (int) round($gross * 0.1);

        return [
            'order_item_id' => OrderItem::factory(),
            'commission_rule_id' => null,
            'rate_type' => CommissionType::Percentage,
            'rate_value' => 10,
            'gross_amount' => $gross,
            'commission_amount' => $commission,
            'net_amount' => $gross - $commission,
        ];
    }
}
