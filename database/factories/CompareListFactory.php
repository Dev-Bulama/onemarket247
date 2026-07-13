<?php

namespace Database\Factories;

use App\Models\CompareList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompareList>
 */
class CompareListFactory extends Factory
{
    protected $model = CompareList::class;

    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
        ];
    }
}
