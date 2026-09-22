<?php

namespace Database\Factories;

use App\Enums\SpotlightDisplayArea;
use App\Models\Product;
use App\Models\ProductSpotlight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductSpotlight>
 */
class ProductSpotlightFactory extends Factory
{
    protected $model = ProductSpotlight::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'category_id' => null,
            'display_area' => SpotlightDisplayArea::Homepage,
            'position' => fake()->numberBetween(0, 10),
            'starts_at' => null,
            'ends_at' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function forArea(SpotlightDisplayArea $area): static
    {
        return $this->state(['display_area' => $area]);
    }

    public function scheduled(\DateTimeInterface $startsAt, \DateTimeInterface $endsAt): static
    {
        return $this->state(['starts_at' => $startsAt, 'ends_at' => $endsAt]);
    }
}
