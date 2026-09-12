<?php

namespace Database\Factories;

use App\Models\SiteVisit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteVisit>
 */
class SiteVisitFactory extends Factory
{
    protected $model = SiteVisit::class;

    public function definition(): array
    {
        return [
            'visitor_id' => fake()->unique()->uuid(),
            'user_id' => null,
            'vendor_id' => null,
            'path' => '/',
            'ip_address' => fake()->ipv4(),
            'country_code' => null,
            'created_at' => now(),
        ];
    }
}
