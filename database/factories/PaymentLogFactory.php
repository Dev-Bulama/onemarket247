<?php

namespace Database\Factories;

use App\Enums\PaymentLogDirection;
use App\Models\Payment;
use App\Models\PaymentLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentLog>
 */
class PaymentLogFactory extends Factory
{
    protected $model = PaymentLog::class;

    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'gateway' => 'paystack',
            'direction' => PaymentLogDirection::Request,
            'payload' => ['note' => fake()->sentence()],
        ];
    }
}
