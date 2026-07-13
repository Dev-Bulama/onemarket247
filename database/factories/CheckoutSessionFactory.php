<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CheckoutSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CheckoutSession>
 */
class CheckoutSessionFactory extends Factory
{
    protected $model = CheckoutSession::class;

    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'idempotency_key' => Str::random(40),
            'subtotal' => 0,
            'discount_amount' => 0,
            'total' => 0,
            'order_id' => null,
            'expires_at' => now()->addMinutes(30),
        ];
    }
}
