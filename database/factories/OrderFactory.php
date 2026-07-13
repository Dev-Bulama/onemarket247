<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->numberBetween(2000, 50000);

        return [
            'customer_id' => User::factory(),
            'shipping_full_name' => fake()->name(),
            'shipping_phone' => fake()->e164PhoneNumber(),
            'shipping_address_line_1' => fake()->streetAddress(),
            'shipping_country_id' => Country::factory(),
            'currency_id' => Currency::factory(),
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'tax_amount' => 0,
            'total' => $subtotal,
            'status' => OrderStatus::PendingPayment,
            'placed_at' => now(),
        ];
    }

    public function guest(): static
    {
        return $this->state(fn () => [
            'customer_id' => null,
            'guest_name' => fake()->name(),
            'guest_email' => fake()->unique()->safeEmail(),
            'guest_phone' => fake()->e164PhoneNumber(),
        ]);
    }
}
