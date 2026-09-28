<?php

namespace Database\Factories;

use App\Models\Disclaimer;
use App\Models\DisclaimerAcceptance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DisclaimerAcceptance>
 */
class DisclaimerAcceptanceFactory extends Factory
{
    protected $model = DisclaimerAcceptance::class;

    public function definition(): array
    {
        return [
            'disclaimer_id' => Disclaimer::factory(),
        ];
    }
}
