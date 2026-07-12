<?php

namespace Database\Factories;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoginHistory>
 */
class LoginHistoryFactory extends Factory
{
    protected $model = LoginHistory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guard' => 'web',
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'device_fingerprint' => sha1(fake()->uuid()),
            'is_new_device' => false,
            'successful' => true,
        ];
    }
}
