<?php

namespace Database\Factories;

use App\Models\DeviceSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceSession>
 */
class DeviceSessionFactory extends Factory
{
    protected $model = DeviceSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'guard' => 'web',
            'session_id' => fake()->unique()->uuid(),
            'device_fingerprint' => sha1(fake()->uuid()),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'last_used_at' => now(),
        ];
    }
}
