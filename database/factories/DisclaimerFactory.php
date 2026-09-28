<?php

namespace Database\Factories;

use App\Enums\DisclaimerTrigger;
use App\Models\Disclaimer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Disclaimer>
 */
class DisclaimerFactory extends Factory
{
    protected $model = Disclaimer::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'content' => fake()->paragraph(),
            'trigger' => DisclaimerTrigger::FirstVisit,
            'requires_acceptance' => true,
            'is_active' => true,
        ];
    }
}
