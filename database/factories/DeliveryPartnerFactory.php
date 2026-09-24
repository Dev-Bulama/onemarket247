<?php

namespace Database\Factories;

use App\Enums\DeliveryPartnerStatus;
use App\Models\DeliveryPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryPartner>
 */
class DeliveryPartnerFactory extends Factory
{
    protected $model = DeliveryPartner::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->e164PhoneNumber(),
            'vehicle_type' => fake()->randomElement(['bike', 'car', 'van']),
            'status' => DeliveryPartnerStatus::Active,
        ];
    }

    public function suspended(): static
    {
        return $this->state(['status' => DeliveryPartnerStatus::Suspended]);
    }

    public function deactivated(): static
    {
        return $this->state(['status' => DeliveryPartnerStatus::Deactivated]);
    }
}
