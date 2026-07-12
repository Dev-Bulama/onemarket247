<?php

namespace Database\Factories;

use App\Enums\StockTransferStatus;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockTransfer>
 */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    public function definition(): array
    {
        return [
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id' => Warehouse::factory(),
            'status' => StockTransferStatus::Pending,
            'requested_at' => now(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => StockTransferStatus::Completed, 'completed_at' => now()]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => StockTransferStatus::Cancelled]);
    }
}
