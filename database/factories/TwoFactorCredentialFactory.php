<?php

namespace Database\Factories;

use App\Models\TwoFactorCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TwoFactorCredential>
 */
class TwoFactorCredentialFactory extends Factory
{
    protected $model = TwoFactorCredential::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'secret' => Str::random(32),
            'recovery_codes' => collect(range(1, 8))->map(fn () => Str::random(10))->all(),
            'confirmed_at' => now(),
        ];
    }
}
