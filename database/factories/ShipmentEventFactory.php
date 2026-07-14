<?php

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentEvent>
 */
class ShipmentEventFactory extends Factory
{
    protected $model = ShipmentEvent::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'status' => ShipmentStatus::Pending,
            'location' => fake()->city(),
            'description' => fake()->sentence(),
            'occurred_at' => now(),
            'created_by' => null,
        ];
    }
}
