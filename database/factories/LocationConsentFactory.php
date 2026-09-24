<?php

namespace Database\Factories;

use App\Models\LocationConsent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationConsent>
 */
class LocationConsentFactory extends Factory
{
    protected $model = LocationConsent::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'is_enabled' => true,
            'enabled_at' => now(),
        ];
    }

    public function disabled(): static
    {
        return $this->state(['is_enabled' => false, 'enabled_at' => null, 'disabled_at' => now()]);
    }
}
