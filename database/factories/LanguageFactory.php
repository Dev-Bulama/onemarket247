<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    protected $model = Language::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'native_name' => fake()->word(),
            'code' => fake()->unique()->languageCode(),
            'direction' => 'ltr',
            'is_default' => false,
            'is_active' => true,
        ];
    }
}
