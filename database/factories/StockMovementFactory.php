<?php

namespace Database\Factories;

use App\Enums\StockMovementBucket;
use App\Enums\StockMovementType;
use App\Models\StockMovement;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'warehouse_stock_id' => WarehouseStock::factory(),
            'type' => StockMovementType::Adjustment,
            'bucket' => StockMovementBucket::OnHand,
            'quantity_delta' => fake()->numberBetween(-10, 10),
            'reason' => fake()->sentence(),
        ];
    }
}
