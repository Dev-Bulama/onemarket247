<?php

namespace Database\Factories;

use App\Enums\AttributeInputType;
use App\Models\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attribute>
 */
class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'input_type' => AttributeInputType::Select,
            'is_filterable' => true,
            'is_variation' => true,
            'sort_order' => 0,
        ];
    }

    public function swatch(): static
    {
        return $this->state(fn () => ['input_type' => AttributeInputType::Swatch]);
    }
}
