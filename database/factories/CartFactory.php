<?php

namespace Database\Factories;

use App\Enums\CartStatus;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'customer_id' => null,
            'session_token' => Str::random(64),
            'status' => CartStatus::Active,
        ];
    }

    public function forCustomer(): static
    {
        return $this->state(fn () => [
            'customer_id' => User::factory(),
            'session_token' => null,
        ]);
    }
}
