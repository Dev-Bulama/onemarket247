<?php

namespace Database\Factories;

use App\Enums\DeliveryAssignmentStatus;
use App\Models\DeliveryAssignment;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryAssignment>
 */
class DeliveryAssignmentFactory extends Factory
{
    protected $model = DeliveryAssignment::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'assignee_name' => fake()->name(),
            'assignee_phone' => fake()->phoneNumber(),
            'status' => DeliveryAssignmentStatus::Assigned,
            'assigned_at' => now(),
            'delivered_at' => null,
            'assigned_by' => null,
        ];
    }
}
