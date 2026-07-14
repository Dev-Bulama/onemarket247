<?php

namespace Database\Factories;

use App\Enums\DeliveryEvidenceType;
use App\Models\DeliveryAssignment;
use App\Models\DeliveryEvidence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryEvidence>
 */
class DeliveryEvidenceFactory extends Factory
{
    protected $model = DeliveryEvidence::class;

    public function definition(): array
    {
        return [
            'delivery_assignment_id' => DeliveryAssignment::factory(),
            'type' => DeliveryEvidenceType::Photo,
            'file_path' => 'delivery-evidence/'.fake()->uuid().'.jpg',
            'recipient_name' => fake()->name(),
            'notes' => null,
        ];
    }
}
