<?php

namespace Database\Factories;

use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentGateway>
 */
class PaymentGatewayFactory extends Factory
{
    protected $model = PaymentGateway::class;

    public function definition(): array
    {
        return [
            'code' => 'paystack',
            'name' => 'Paystack',
            'is_active' => true,
            'public_key' => 'pk_test_'.fake()->uuid(),
            'secret_key' => 'sk_test_'.fake()->uuid(),
            'webhook_secret' => 'sk_test_'.fake()->uuid(),
            'config' => null,
        ];
    }
}
