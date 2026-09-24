<?php

namespace Database\Factories;

use App\Enums\DeliveryRequestStatus;
use App\Models\DeliveryRequest;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryRequest>
 */
class DeliveryRequestFactory extends Factory
{
    protected $model = DeliveryRequest::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'status' => DeliveryRequestStatus::Pending,
            'delivery_fee' => fake()->numberBetween(300, 3000),
        ];
    }
}
