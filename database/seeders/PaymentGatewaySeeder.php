<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use App\Services\Payment\PaystackGateway;
use Illuminate\Database\Seeder;

/**
 * Populates the 'paystack' row from .env on first run only — once a
 * payment_gateways row exists, key rotation happens through
 * PaymentGatewayResource in the admin panel, never by re-running this
 * seeder (which would overwrite an admin's rotated keys with stale env
 * values).
 */
class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        if (PaymentGateway::where('code', PaystackGateway::CODE)->exists()) {
            return;
        }

        PaymentGateway::create([
            'code' => PaystackGateway::CODE,
            'name' => 'Paystack',
            'is_active' => filled(config('services.paystack.secret_key')),
            'public_key' => config('services.paystack.public_key'),
            'secret_key' => config('services.paystack.secret_key'),
            'webhook_secret' => config('services.paystack.webhook_secret'),
        ]);
    }
}
