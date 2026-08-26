<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\PaymentGateway;
use App\Support\Api\ApiResponse;
use App\Support\PriceDisplay;
use Illuminate\Http\JsonResponse;

/**
 * A single call the mobile app makes on launch to learn what's actually
 * turned on server-side (which payment methods are live, the default
 * language/currency) rather than hardcoding assumptions that drift from
 * the admin's real settings.
 */
class ConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'default_currency' => PriceDisplay::baseCurrencyCode(),
            'default_language' => Language::where('is_default', true)->value('code') ?? 'en',
            'payment_methods' => $this->paymentMethods(),
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function paymentMethods(): array
    {
        $methods = ['bank_transfer'];

        if (PaymentGateway::where('code', 'paystack')->where('is_active', true)->exists()) {
            $methods[] = 'paystack';
        }

        return $methods;
    }
}
