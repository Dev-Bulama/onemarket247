<?php

namespace Database\Factories;

use App\Enums\StoreStaffStatus;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreStaff>
 */
class StoreStaffFactory extends Factory
{
    protected $model = StoreStaff::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'status' => StoreStaffStatus::Active,
            'invited_at' => now()->subDay(),
            'joined_at' => now(),
        ];
    }
}
